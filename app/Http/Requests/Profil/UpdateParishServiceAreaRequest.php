<?php

namespace App\Http\Requests\Profil;

use App\Concerns\ProfilValidationRules;
use App\Models\ParishServiceArea;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates updating the service-area narrative singleton.
 *
 * @see docs/DECISIONS.md D-52
 */
class UpdateParishServiceAreaRequest extends FormRequest
{
    use ProfilValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', ParishServiceArea::current()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeHtmlFields(['content']);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'content' => ['nullable', 'string', 'max:500000'],
            'map_media_id' => ['nullable', 'integer', $this->readyMediaRule()],
            'map_url' => ['nullable', 'url', 'max:500'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.max' => 'Narasi wilayah terlalu panjang untuk disimpan.',
            'map_media_id.exists' => $this->mediaReadyMessage(),
            'map_url.url' => 'Tautan peta tidak valid.',
            'is_published.required' => 'Tentukan apakah narasi wilayah diterbitkan.',
        ];
    }
}
