<?php

namespace App\Http\Requests\Media;

use App\Rules\IsProcessableImage;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates a media upload.
 *
 * Authorization lives here and nowhere else, per ARCHITECTURE.md Part C
 * section 8: this route has a form request, so the form request is the single
 * place the check happens.
 *
 * @see docs/DECISIONS.md D-24
 */
class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('media.create');
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'file' => [
                'required',
                'file',
                // IsProcessableImage reads the bytes, which is what XC-M3 asks
                // for. The kilobyte rule covers the size half of T20.
                new IsProcessableImage,
                'max:'.(int) config('media.max_upload_kilobytes'),
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Pilih berkas gambar terlebih dahulu.',
            'file.max' => 'Ukuran gambar maksimal :max kilobyte.',
            'alt_text.max' => 'Teks alternatif maksimal :max karakter.',
        ];
    }
}
