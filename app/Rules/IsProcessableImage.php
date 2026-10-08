<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use Illuminate\Translation\PotentiallyTranslatedString;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

/**
 * Confirms an upload really is one of the image types the pipeline can process.
 *
 * XC-M3 requires the MIME type to be validated on the server rather than trusting
 * the extension, and this is where that happens. Two independent checks are used
 * because neither alone is enough:
 *
 *   - finfo reads the magic bytes, so a PHP script renamed to .jpg is text and
 *     gets refused.
 *   - getimagesize confirms the bytes really parse as that image type, so a file
 *     that merely claims image/jpeg does not get past this and blow up later
 *     inside GD with a much less useful message.
 *
 * It also refuses anything larger than the configured pixel budget. A tiny file
 * can decompress into a very large image in memory, and the check has to happen
 * before GD decodes anything.
 *
 * @see docs/DECISIONS.md D-24
 */
class IsProcessableImage implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Berkas :attribute gagal diunggah.');

            return;
        }

        $allowed = (array) config('media.mime_types');

        try {
            $detected = (string) $value->getMimeType();
        } catch (FileException) {
            $fail('Berkas :attribute tidak dapat dibaca.');

            return;
        }

        if (! in_array($detected, $allowed, true)) {
            $fail('Berkas :attribute harus berupa gambar JPG atau PNG.');

            return;
        }

        $size = @getimagesize($value->getRealPath());

        if ($size === false) {
            $fail('Berkas :attribute bukan gambar yang dapat diproses.');

            return;
        }

        // getimagesize reports the type from the file contents too, so a JPEG
        // renamed to .png is caught here even where finfo was lenient.
        if ($size['mime'] !== $detected) {
            $fail('Isi berkas :attribute tidak cocok dengan format gambarnya.');

            return;
        }

        $width = (int) $size[0];
        $height = (int) $size[1];

        if ($width < 1 || $height < 1) {
            $fail('Dimensi berkas :attribute tidak valid.');

            return;
        }

        if ($width > (int) config('media.max_width') || $height > (int) config('media.max_height')) {
            $fail('Dimensi berkas :attribute melebihi batas yang diizinkan.');

            return;
        }
    }
}
