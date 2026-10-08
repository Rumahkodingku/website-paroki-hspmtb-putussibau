<?php

namespace App\Exceptions;

use App\Models\Media;
use RuntimeException;

/**
 * Raised when an upload passes validation but cannot actually be processed.
 *
 * Distinct from a validation failure on purpose. A bad MIME type is the uploader's
 * mistake and belongs in a form error, while a file that validates as image/jpeg
 * and then refuses to decode is a processing failure, which Phase 01 section 21
 * says must land in failed_jobs with a useful log and must not corrupt the
 * database.
 */
class UnprocessableImage extends RuntimeException
{
    public static function unreadable(Media $media): self
    {
        return new self("Gambar pada media #{$media->getKey()} tidak dapat dibaca.");
    }

    public static function unreadablePath(string $path): self
    {
        return new self("Gambar di {$path} tidak dapat dibaca.");
    }

    public static function resizeFailed(int $width, int $height): self
    {
        return new self("Gagal mengubah ukuran gambar menjadi {$width}x{$height}.");
    }

    public static function encodeFailed(): self
    {
        return new self('Gagal mengubah gambar menjadi WebP.');
    }
}
