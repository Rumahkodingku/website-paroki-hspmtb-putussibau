<?php

namespace App\Http\Requests\Installation;

use Illuminate\Foundation\Http\FormRequest;

/**
 * The identity fields plus the basic application configuration the product
 * agreed on.
 *
 * Nothing else is accepted: the controller stores exactly the validated array,
 * and SiteSettingsService drops any key that is not declared in
 * config/site-settings.php. Optional blanks become null so "never configured"
 * stays distinguishable from "deliberately empty".
 */
final class StoreApplicationConfigurationRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    private const KEYS = [
        'parish_name',
        'parish_short_name',
        'tagline',
        'short_description',
        'motto_verse',
        'patron_saint',
        'app_name',
        'app_url',
        'timezone',
        'locale',
        'environment',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $values = [];

        foreach (self::KEYS as $key) {
            $value = $this->input($key);

            if (is_string($value)) {
                $value = trim($value);
            }

            $values[$key] = is_string($value) && $value !== '' ? $value : null;
        }

        $this->merge($values);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'parish_name' => ['required', 'string', 'max:150'],
            'parish_short_name' => ['required', 'string', 'max:50'],
            'tagline' => ['nullable', 'string', 'max:150'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'motto_verse' => ['nullable', 'string', 'max:500'],
            'patron_saint' => ['nullable', 'string', 'max:100'],
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url', 'max:255'],
            'timezone' => ['required', 'string', 'timezone'],
            'locale' => ['required', 'string', 'in:id'],
            'environment' => ['required', 'string', 'in:production,local'],
        ];
    }
}
