<?php

namespace App\Http\Requests\Profil;

use App\Concerns\ProfilValidationRules;
use App\Models\ParishProfile;
use App\Rules\YoutubeUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates the profile-content singleton.
 *
 * @see docs/DECISIONS.md D-78
 */
class UpdateParishProfileRequest extends FormRequest
{
    use ProfilValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', ParishProfile::current()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeHtmlFields(['description']);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:500000'],
            'established_year' => ['nullable', 'integer', 'between:1000,'.date('Y')],
            'cover_media_id' => ['nullable', 'integer', $this->readyMediaRule()],
            'cover_alt' => ['nullable', 'string', 'max:255', 'required_with:cover_media_id'],
            'video_url' => ['nullable', 'string', 'max:500', new YoutubeUrl],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'description.max' => 'Deskripsi profil terlalu panjang untuk disimpan.',
            'established_year.between' => 'Tahun berdiri harus di antara 1000 dan tahun ini.',
            'cover_media_id.exists' => $this->mediaReadyMessage(),
            'cover_alt.required_with' => 'Teks alternatif sampul wajib diisi saat gambar dipilih.',
        ];
    }
}
