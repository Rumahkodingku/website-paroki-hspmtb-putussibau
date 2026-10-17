<?php

namespace App\Http\Requests\Galeri;

use App\Models\GalleryAlbum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Reordering an album's photos.
 *
 * Only the shape is validated here — a list of integer ids. That an id belongs to
 * *this* album is checked by ReorderGalleryPhotosAction, which is the only place
 * that can fail the whole transaction rather than reordering a subset.
 *
 * @see docs/DECISIONS.md D-65
 */
class ReorderGalleryPhotosRequest extends FormRequest
{
    public function authorize(): bool
    {
        $album = $this->route('gallery');

        return $album instanceof GalleryAlbum
            && $this->user()?->can('update', $album) === true;
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'distinct'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'ids.required' => 'Daftar urutan tidak boleh kosong.',
            'ids.array' => 'Daftar urutan tidak valid.',
            'ids.*.distinct' => 'Daftar urutan memuat item yang sama lebih dari sekali.',
        ];
    }
}
