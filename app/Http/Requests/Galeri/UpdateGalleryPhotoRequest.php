<?php

namespace App\Http\Requests\Galeri;

use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Editing a photo's caption and alt text.
 *
 * The photo must belong to the album in the route: the route model binding
 * resolves a photo by id globally, so a photo from another album could be
 * targeted otherwise. That check lives here (rather than trusting the binding)
 * because it is a fact about this request's two route segments.
 *
 * @see docs/DECISIONS.md D-65
 */
class UpdateGalleryPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $album = $this->route('gallery');

        return $album instanceof GalleryAlbum
            && $this->user()?->can('update', $album) === true;
    }

    protected function prepareForValidation(): void
    {
        $input = [];

        foreach (['caption', 'alt_text'] as $field) {
            $value = $this->input($field);
            $input[$field] = is_string($value) ? trim($value) : $value;

            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        $this->merge($input);
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'caption' => ['nullable', 'string', 'max:255'],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'caption.max' => 'Keterangan foto maksimal :max karakter.',
            'alt_text.max' => 'Teks alternatif maksimal :max karakter.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [$this->assertPhotoBelongsToAlbum()];
    }

    private function assertPhotoBelongsToAlbum(): callable
    {
        return function (Validator $validator): void {
            $album = $this->route('gallery');
            $photo = $this->route('photo');

            if (
                $album instanceof GalleryAlbum
                && $photo instanceof GalleryPhoto
                && $photo->gallery_album_id !== $album->getKey()
            ) {
                $validator->errors()->add('photo', 'Foto ini bukan bagian dari album tersebut.');
            }
        };
    }
}
