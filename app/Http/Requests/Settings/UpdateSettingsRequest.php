<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a site settings save.
 *
 * The payload is shaped by whatever the form posts, so this has to reject keys
 * the configuration does not know about. Without that check, anyone holding
 * settings.update could write arbitrary rows into the table, which is the kind
 * of thing that later gets read back as configuration.
 *
 * Authorization lives here and nowhere else, per ARCHITECTURE.md Part C section
 * 8: this route has a form request, so the form request is the single place the
 * check happens.
 *
 * @see docs/DECISIONS.md D-23
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
     * Normalise before validating.
     *
     * The switches send real booleans, and value is a LONGTEXT column, so they
     * become the same '1' and '0' the service stores. Doing it here keeps every
     * rule below a plain string rule instead of one that has to accept either
     * shape.
     */
    protected function prepareForValidation(): void
    {
        $settings = $this->input('settings');

        if (! is_array($settings)) {
            return;
        }

        $this->merge([
            'settings' => array_map(
                fn (mixed $value): mixed => is_bool($value) ? ($value ? '1' : '0') : $value,
                $settings,
            ),
        ]);
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
            'settings.*' => ['string', 'max:2048'],
        ];

        // Per-key rules from the configuration override the generic ones above.
        foreach ((array) config('site-settings.rules') as $key => $rule) {
            $rules["settings.{$key}"] = explode('|', (string) $rule);
        }

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
