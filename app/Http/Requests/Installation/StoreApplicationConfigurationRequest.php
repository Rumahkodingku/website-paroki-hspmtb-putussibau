<?php

namespace App\Http\Requests\Installation;

use App\Rules\GoogleMapsLink;
use App\Support\MapsUrl;
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
        'contact_address',
        'contact_phone',
        'contact_whatsapp',
        'contact_email',
        'maps_link',
        'social_facebook',
        'social_instagram',
        'social_youtube',
        'social_whatsapp_channel',
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

            if ($key === 'maps_link' && is_string($value) && $value !== '') {
                $value = MapsUrl::extract($value) ?? $value;
            }

            $values[$key] = is_string($value) && $value !== '' ? $value : null;
        }

        $this->merge($values);
    }

    /**
     * @return array<string, mixed>
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
            'contact_address' => ['nullable', 'string', 'max:2048'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'contact_whatsapp' => ['nullable', 'string', 'max:32'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'maps_link' => ['nullable', 'string', new GoogleMapsLink, 'max:2048'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'social_whatsapp_channel' => ['nullable', 'url', 'max:255'],
            'app_name' => ['required', 'string', 'max:100'],
            'app_url' => ['required', 'url', 'max:255'],
            'timezone' => ['required', 'string', 'timezone'],
            'locale' => ['required', 'string', 'in:id'],
            'environment' => ['required', 'string', 'in:production,local'],
        ];
    }
}
