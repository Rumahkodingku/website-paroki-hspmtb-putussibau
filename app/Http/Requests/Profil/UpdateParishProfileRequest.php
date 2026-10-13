<?php

namespace App\Http\Requests\Profil;

use App\Concerns\ProfilValidationRules;
use App\Models\ParishProfile;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates updating the parish profile singleton.
 *
 * The authorization point for the update, and the place the two rich-text
 * fields are sanitized before the rules run.
 *
 * @see docs/DECISIONS.md D-52
 */
class UpdateParishProfileRequest extends FormRequest
{
    use ProfilValidationRules;

    public function authorize(): bool
    {
        // The singleton has no route parameter, so the policy is handed the
        // current instance (or an empty one) rather than a class name.
        return $this->user()?->can('update', ParishProfile::current()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeHtmlFields(['description', 'history_description']);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string', 'max:500000'],
            'history_description' => ['nullable', 'string', 'max:500000'],
            'address' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'url', 'max:255'],
            'established_year' => ['nullable', 'integer', 'min:1000', 'max:'.date('Y')],
            'logo_media_id' => ['nullable', 'integer', $this->readyMediaRule()],
            'logo_alt' => ['nullable', 'string', 'max:255'],
            'cover_media_id' => ['nullable', 'integer', $this->readyMediaRule()],
            'cover_alt' => ['nullable', 'string', 'max:255'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'video_url' => ['nullable', 'url', 'max:500'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama paroki wajib diisi.',
            'name.max' => 'Nama paroki maksimal :max karakter.',
            'short_description.max' => 'Deskripsi singkat maksimal :max karakter.',
            'description.max' => 'Deskripsi terlalu panjang untuk disimpan.',
            'history_description.max' => 'Narasi sejarah terlalu panjang untuk disimpan.',
            'email.email' => 'Alamat surel tidak valid.',
            'website.url' => 'Alamat situs web tidak valid.',
            'established_year.integer' => 'Tahun berdiri harus berupa angka.',
            'established_year.min' => 'Tahun berdiri tidak masuk akal.',
            'established_year.max' => 'Tahun berdiri tidak boleh di masa depan.',
            'logo_media_id.exists' => $this->mediaReadyMessage(),
            'cover_media_id.exists' => $this->mediaReadyMessage(),
            'map_url.url' => 'Tautan peta tidak valid.',
            'video_url.url' => 'Tautan video tidak valid.',
            'is_published.required' => 'Tentukan apakah profil diterbitkan.',
        ];
    }
}
