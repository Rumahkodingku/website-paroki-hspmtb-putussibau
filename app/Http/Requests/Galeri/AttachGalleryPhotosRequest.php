<?php

namespace App\Http\Requests\Galeri;

use App\Enums\MediaStatus;
use App\Models\GalleryAlbum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Attaching one or more already-processed uploads to an album.
 *
 * Only `ready` media is accepted, because an upload is queued: a pending row
 * would publish an image that 404s. The browser waits for the same condition
 * before it posts (media-client.ts), so both ends agree rather than the server
 * being the only place the rule exists.
 *
 * @see docs/DECISIONS.md D-65
 */
class AttachGalleryPhotosRequest extends FormRequest
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
            'media_ids' => ['required', 'array', 'min:1', 'max:50'],
            'media_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('media', 'id')->where('status', MediaStatus::Ready->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'media_ids.required' => 'Tidak ada foto yang dipilih.',
            'media_ids.array' => 'Daftar foto tidak valid.',
            'media_ids.min' => 'Pilih minimal satu foto.',
            'media_ids.max' => 'Maksimal :max foto per unggahan.',
            'media_ids.*.exists' => 'Foto yang dipilih belum selesai diproses. Tunggu sebentar lalu coba lagi.',
            'media_ids.*.distinct' => 'Foto yang sama dipilih lebih dari sekali.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['media_ids' => 'daftar foto'];
    }
}
