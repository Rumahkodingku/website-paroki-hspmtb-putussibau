<?php

namespace App\Http\Requests\Galeri;

use App\Models\GalleryAlbum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Choosing the album cover.
 *
 * The photo must belong to this album, which the exists rule enforces on the
 * `gallery_album_id`, so a cover can never point at a photo from another album.
 * Nullable on purpose: clearing the cover falls back to the first photo (GAL-07).
 *
 * @see docs/DECISIONS.md D-65
 */
class SetGalleryCoverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $album = $this->route('gallery');

        return $album instanceof GalleryAlbum
            && $this->user()?->can('update', $album) === true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('photo_id') === '') {
            $this->merge(['photo_id' => null]);
        }
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        $album = $this->route('gallery');
        $albumId = $album instanceof GalleryAlbum ? $album->getKey() : null;

        return [
            'photo_id' => [
                'nullable',
                'integer',
                Rule::exists('gallery_photos', 'id')->where('gallery_album_id', $albumId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'photo_id.exists' => 'Foto sampul harus berasal dari album ini.',
        ];
    }
}
