<?php

namespace App\Http\Requests\Galeri;

use App\Concerns\GalleryValidationRules;
use App\Models\GalleryAlbum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-65
 */
class UpdateGalleryAlbumRequest extends FormRequest
{
    use GalleryValidationRules;

    public function authorize(): bool
    {
        $album = $this->album();

        return $album !== null && $this->user()?->can('update', $album) === true;
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
        return $this->galleryAlbumRules($this->album());
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

    private function album(): ?GalleryAlbum
    {
        $album = $this->route('gallery');

        return $album instanceof GalleryAlbum ? $album : null;
    }
}
