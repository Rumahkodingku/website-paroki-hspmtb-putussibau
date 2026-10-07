<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $rules = [
            'settings' => ['required', 'array'],
            'settings.*' => ['string', 'max:2048', Rule::in($this->knownKeys())],
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
            'settings.*.in' => 'Pengaturan :attribute tidak dikenal.',
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
