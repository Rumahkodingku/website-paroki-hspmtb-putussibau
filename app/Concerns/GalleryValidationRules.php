<?php

namespace App\Concerns;

use App\Models\GalleryAlbum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * The rules, messages and normalization for a gallery album, shared by the store
 * and update request so they cannot drift apart.
 *
 * Three things are deliberate:
 *
 * 1. `slug` is unique across soft-deleted rows (Rule::unique without
 *    withoutTrashed), because the public route binds {gallery:slug} and two rows
 *    answering one slug makes the binding ambiguous (D-44/D-65).
 *
 * 2. `published_at` is not in the rules at all: it is derived in
 *    SaveGalleryAlbumAction, never posted, so a browser cannot set a publication
 *    date the album was not published on.
 *
 * 3. `cover_photo_id` is not here either. The cover is chosen on the photo
 *    management screen and set through SetGalleryCoverRequest, which is the only
 *    place that knows the photo has to belong to this album.
 *
 * @see docs/DECISIONS.md D-65
 */
trait GalleryValidationRules
{
    /**
     * @param  GalleryAlbum|null  $ignore  the record being updated, so it keeps its own slug
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function galleryAlbumRules(?GalleryAlbum $ignore = null): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:180',
                // No withoutTrashed(): the public route binds {gallery:slug},
                // so a slug used once is never reused (D-44, D-65).
                Rule::unique('gallery_albums', 'slug')->ignore($ignore?->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:5000'],
            'event_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:255'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function galleryAlbumMessages(): array
    {
        return [
            'title.required' => 'Judul album wajib diisi.',
            'title.max' => 'Judul album maksimal :max karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'slug.unique' => 'Slug sudah dipakai album lain.',
            'description.max' => 'Deskripsi maksimal :max karakter.',
            'event_date.date' => 'Tanggal kegiatan tidak valid.',
            'location.max' => 'Lokasi maksimal :max karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function galleryAlbumAttributes(): array
    {
        return [
            'title' => 'judul album',
            'slug' => 'slug',
            'description' => 'deskripsi',
            'event_date' => 'tanggal kegiatan',
            'location' => 'lokasi',
            'is_published' => 'publikasi',
        ];
    }

    /**
     * Coerce and clean the payload before the rules run.
     */
    protected function prepareGalleryAlbumInput(): void
    {
        $input = [];

        foreach (['title', 'slug', 'description', 'location'] as $field) {
            $value = $this->input($field);
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        // An untouched optional field is absent, not an empty string.
        foreach (['description', 'location', 'event_date'] as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        if (($input['slug'] ?? null) === null || $input['slug'] === '') {
            $input['slug'] = Str::slug((string) $this->input('title'));
        }

        $input['is_published'] = (bool) filter_var($this->input('is_published'), FILTER_VALIDATE_BOOLEAN);

        $this->merge($input);
    }
}
