<?php

namespace App\Http\Requests\Settings;

use App\Rules\GoogleMapsLink;
use App\Services\HtmlSanitizer;
use App\Support\MapsUrl;
use App\Support\OfficeHours;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates a site settings save.
 *
 * The payload is shaped by whatever the form posts, so this has to reject keys
 * the configuration does not know about. Without that check, anyone holding
 * settings.update could write arbitrary rows into the table, which is the kind
 * of thing that later gets read back as configuration.
 *
 * It also has to clean the rich text. PRD D-14 and XC-S1 require server-side
 * sanitization for any HTML the application stores, and the editor being in the
 * browser is not that guarantee: the request body is whatever the client sent.
 *
 * Authorization lives here and nowhere else, per ARCHITECTURE.md Part C section
 * 8: this route has a form request, so the form request is the single place the
 * check happens.
 *
 * @see docs/DECISIONS.md D-23, D-25
 */
class UpdateSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('settings.update');
    }

    /**
     * Normalise and sanitize before validating.
     *
     * The switches send real booleans, and value is a LONGTEXT column, so they
     * become the same '1' and '0' the service stores. Doing it here keeps every
     * rule below a plain string rule instead of one that has to accept either
     * shape.
     *
     * Sanitization happens in the same pass, and before rules() runs rather than
     * after, for two reasons. The first is ordering: the length rule has to
     * measure what will be stored, not what was posted, or an admin is told a
     * policy is too long when it is not. The second is that a rule can only
     * ever reject; there is no way to make it rewrite the value it validated.
     */
    protected function prepareForValidation(): void
    {
        $settings = $this->input('settings');

        if (! is_array($settings)) {
            return;
        }

        $normalised = $this->normaliseAll($settings);

        if (array_key_exists('maps_link', $normalised)) {
            $normalised['maps_link'] = $this->normaliseMapsLink(
                $normalised['maps_link'],
            );
        }

        $this->merge([
            'settings' => $normalised,
        ]);
    }

    /**
     * Store the URL out of whatever the admin pasted, never the iframe markup.
     *
     * KON-02 lets an administrator paste either a link or a whole <iframe>; only
     * the src is stored so the column holds one value the reader can parse. The
     * value is rewritten here because a validation rule can reject a value but
     * cannot replace it.
     *
     * An empty value becomes null so a parish without a map can clear the
     * field. A non-empty value that yields no src is left as typed, so the
     * GoogleMapsLink rule reports it instead of the request silently storing
     * null. This is a deliberate change in stored form and is recorded in D-67.
     */
    private function normaliseMapsLink(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return MapsUrl::extract($value) ?? $value;
    }

    /**
     * Convert every posted value into the form the rules and the service expect.
     *
     * @param  array<mixed>  $settings
     * @return array<mixed>
     */
    private function normaliseAll(array $settings): array
    {
        $normalised = [];

        /** @var mixed $value */
        foreach ($settings as $key => $value) {
            $normalised[$key] = $this->normaliseValue(is_string($key) ? $key : '', $value);
        }

        return $normalised;
    }

    /**
     * Turn one incoming value into the value that will be stored.
     *
     * Three cases, in the order they have to happen:
     *
     * 1. Booleans become '1' and '0'. The column is a LONGTEXT, and the switches
     *    are the only thing in this form that sends a real boolean.
     * 2. Keys declared as html in the configuration are sanitized. Which keys
     *    those are comes from the configuration rather than from a list here, so
     *    adding a rich text setting is a one-line change in one file.
     * 3. Everything else is left alone and validated as the plain string it is.
     *
     * A null is preserved rather than turned into an empty string. The service
     * stores null for a setting that was never set, and clearing a field should
     * not be indistinguishable from having typed nothing into it on a page that
     * has a fallback default.
     */
    private function normaliseValue(string $key, mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if ($this->isRichText($key) && is_string($value)) {
            return app(HtmlSanitizer::class)->clean($value);
        }

        return $value;
    }

    /**
     * Whether the configuration declares this key as holding HTML.
     */
    private function isRichText(string $key): bool
    {
        return config("site-settings.types.{$key}") === 'html';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $known = $this->knownKeys();

        $rules = [
            'settings' => [
                'required',
                'array',
                /*
                 * Rejects keys the configuration does not know about.
                 *
                 * This has to be a closure on the array rather than Rule::in on
                 * settings.*, because Rule::in checks the value of each field
                 * against the list, and here the list is of field names.
                 */
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $unknown = array_diff(array_keys((array) $value), $this->knownKeys());

                    if ($unknown !== []) {
                        // Built inline rather than via a placeholder: the second
                        // argument of fail() is a set of translation
                        // replacements, not validator message parameters.
                        $fail('Bagian ini memuat pengaturan yang tidak dikenal: '.implode(', ', $unknown));
                    }
                },
            ],
            'settings.*' => ['nullable', 'string', 'max:2048'],
        ];

        /*
         * office_hours is the one structured setting. Defining it explicitly
         * replaces the generic `settings.*` string rule for that key, which is
         * what lets an array through; the per-day rules below then validate the
         * shape. A string is accepted so a legacy free-text value can be saved
         * back unchanged and is never lost before the admin re-enters it.
         */
        $rules['settings.office_hours'] = [
            'nullable',
            function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value === null || is_string($value)) {
                    return;
                }

                if (! is_array($value)) {
                    $fail('Format jam layanan tidak valid.');

                    return;
                }

                foreach (OfficeHours::DAYS as $day => $label) {
                    if (! array_key_exists($day, $value)) {
                        $fail("Jadwal hari {$label} tidak lengkap.");

                        return;
                    }
                }
            },
        ];
        $rules['settings.office_hours.*.is_open'] = ['required', 'boolean'];
        $rules['settings.office_hours.*.open_time'] = ['nullable', 'date_format:H:i'];
        $rules['settings.office_hours.*.close_time'] = ['nullable', 'date_format:H:i'];

        // Per-key rules from the configuration override the generic ones above.
        foreach ((array) config('site-settings.rules') as $key => $rule) {
            $rules["settings.{$key}"] = explode('|', (string) $rule);
        }

        /*
         * The map link rule is an object, and config/site-settings.php only
         * holds string rules that explode on "|". It is attached here rather
         * than declared there so the allowlist stays in the configuration while
         * the parsing stays in one class. The custom rule runs before max so an
         * invalid host is reported as an invalid map rather than as a long
         * string.
         */
        $rules['settings.maps_link'] = [
            'nullable',
            'string',
            new GoogleMapsLink,
            'max:2048',
        ];

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'settings.required' => 'Bagian pengaturan wajib diisi.',
            'settings.array' => 'Format pengaturan tidak valid.',
            'settings.office_hours.*.is_open.boolean' => ':attribute harus bernilai aktif atau nonaktif.',
            'settings.office_hours.*.open_time.date_format' => ':attribute harus dalam format jam HH:mm.',
            'settings.office_hours.*.close_time.date_format' => ':attribute harus dalam format jam HH:mm.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * Named per day so a message reads "Jam buka Senin wajib diisi." rather than
     * leaking the dotted path. Built from the day list so adding a day to
     * OfficeHours keeps this in step.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach (OfficeHours::DAYS as $day => $label) {
            $attributes["settings.office_hours.{$day}.is_open"] = "Status {$label}";
            $attributes["settings.office_hours.{$day}.open_time"] = "Jam buka {$label}";
            $attributes["settings.office_hours.{$day}.close_time"] = "Jam tutup {$label}";
        }

        return $attributes;
    }

    /**
     * Cross-field checks a per-field rule cannot express.
     *
     * An open day needs both times, and its closing time has to be later than
     * its opening time. A closed day needs neither, so its retained times are
     * left alone. This runs only for the structured form; a legacy string has
     * nothing to check.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $officeHours = $this->input('settings.office_hours');

                if (! is_array($officeHours)) {
                    return;
                }

                foreach (OfficeHours::DAYS as $day => $label) {
                    $entry = $officeHours[$day] ?? null;

                    if (! is_array($entry) || ! ($entry['is_open'] ?? false)) {
                        continue;
                    }

                    $open = $entry['open_time'] ?? null;
                    $close = $entry['close_time'] ?? null;

                    if (! is_string($open) || trim($open) === '') {
                        $validator->errors()->add(
                            "settings.office_hours.{$day}.open_time",
                            "Jam buka {$label} wajib diisi saat pelayanan aktif."
                        );
                    }

                    if (! is_string($close) || trim($close) === '') {
                        $validator->errors()->add(
                            "settings.office_hours.{$day}.close_time",
                            "Jam tutup {$label} wajib diisi saat pelayanan aktif."
                        );
                    }

                    if (
                        is_string($open) && is_string($close)
                        && trim($open) !== '' && trim($close) !== ''
                        && strtotime('1970-01-01 '.$close) <= strtotime('1970-01-01 '.$open)
                    ) {
                        $validator->errors()->add(
                            "settings.office_hours.{$day}.close_time",
                            "Jam tutup {$label} harus lebih akhir daripada jam buka."
                        );
                    }
                }
            },
        ];
    }

    /**
     * The validated settings, ready for SiteSettingsService::setMany().
     *
     * @return array<string, mixed>
     */
    public function settingsPayload(): array
    {
        /** @var array<string, array<string, mixed>> $validated */
        $validated = $this->validated();

        return $validated['settings'];
    }

    /**
     * The keys that exist in the configuration.
     *
     * @return list<string>
     */
    private function knownKeys(): array
    {
        $groups = (array) config('site-settings.groups');

        return array_values(array_unique(array_merge(...array_values($groups))));
    }
}
