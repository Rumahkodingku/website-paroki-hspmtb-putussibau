<?php

namespace App\Http\Requests\Galeri;

use App\Concerns\GalleryValidationRules;
use App\Models\GalleryAlbum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-65
 */
class StoreGalleryAlbumRequest extends FormRequest
{
    use GalleryValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', GalleryAlbum::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareGalleryAlbumInput();
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->galleryAlbumRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->galleryAlbumMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->galleryAlbumAttributes();
    }
}
