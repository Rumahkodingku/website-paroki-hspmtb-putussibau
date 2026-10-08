<?php

namespace App\Services;

use App\Exceptions\UnprocessableImage;
use App\Models\Media;
use App\Models\MediaVariant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Turns an uploaded original into published WebP variants.
 *
 * Nine steps from Phase 01 section 20, in order: the caller has already done MIME
 * and size validation, and this class reads the file, applies the EXIF
 * orientation, resizes, re-encodes, stores and records.
 *
 * EXIF removal is a side effect rather than a step. XC-M2 requires EXIF including
 * GPS to be gone from every published image, and GD simply never writes an EXIF
 * block, so anything that goes through here comes out without one. There is no
 * separate stripping call to forget to add, and no code path that can skip it.
 *
 * The orientation read does matter though, and it is the reason this class exists
 * rather than three lines in the job: a phone photo is often stored sideways with
 * an Orientation tag, and ignoring it publishes every such photo rotated.
 *
 * @see docs/DECISIONS.md D-24
 */
class ImageProcessor
{
    /**
     * Read the original's own dimensions and type, and record them on the row.
     *
     * Called before any variant exists so the media list can show a size, and so
     * a file that passes validation but will not decode is discovered here with a
     * clear message rather than inside the resize.
     */
    public function inspect(Media $media): void
    {
        $path = Storage::disk($media->disk)->path($media->path);

        $size = @getimagesize($path);

        if ($size === false) {
            throw UnprocessableImage::unreadable($media);
        }

        $media->forceFill([
            'width' => (int) $size[0],
            'height' => (int) $size[1],
            'mime_type' => $size['mime'],
        ])->save();
    }

    /**
     * Produce every configured variant and record it.
     *
     * An original narrower than a variant width gets no upscaled copy. Enlarging a
     * small photo produces a larger file that looks worse and weighs more, which
     * is the opposite of what the pipeline is for.
     *
     * @return list<MediaVariant>
     */
    public function variants(Media $media): array
    {
        $originalDisk = Storage::disk($media->disk);
        $variantDisk = Storage::disk((string) config('media.variant_disk'));

        $resource = $this->decode($originalDisk->path($media->path), $media->mime_type);

        try {
            $variants = [];

            foreach ((array) config('media.variants') as $name => $targetWidth) {
                $targetWidth = (int) $targetWidth;

                $sourceWidth = imagesx($resource);
                $sourceHeight = imagesy($resource);

                // Never upscale.
                $width = min($targetWidth, $sourceWidth);
                $height = max(1, (int) round($sourceHeight * ($width / $sourceWidth)));

                $resized = $this->resize($resource, $width, $height);

                $path = $this->variantPath($media, (string) $name);

                $variantDisk->put($path, $this->encodeWebp($resized));

                imagedestroy($resized);

                $variants[] = $this->recordVariant(
                    $media,
                    name: (string) $name,
                    path: $path,
                    width: $width,
                    height: $height,
                    fileSize: (int) $variantDisk->size($path),
                );
            }

            return $variants;
        } finally {
            imagedestroy($resource);
        }
    }

    /**
     * The dimensions and mime type of a file on disk, without decoding it.
     *
     * @return array{width: int, height: int, mime_type: string}
     */
    public function measure(string $absolutePath): array
    {
        $size = @getimagesize($absolutePath);

        if ($size === false) {
            throw UnprocessableImage::unreadablePath($absolutePath);
        }

        return [
            'width' => (int) $size[0],
            'height' => (int) $size[1],
            'mime_type' => $size['mime'],
        ];
    }

    /**
     * Decode a file into a GD resource, uprighting it first if EXIF says so.
     */
    private function decode(string $absolutePath, string $mimeType): \GdImage
    {
        $image = match ($mimeType) {
            'image/png' => @imagecreatefrompng($absolutePath),
            default => @imagecreatefromjpeg($absolutePath),
        };

        if ($image === false) {
            throw UnprocessableImage::unreadablePath($absolutePath);
        }

        return $this->applyOrientation($image, $absolutePath);
    }

    /**
     * Rotate or mirror according to the EXIF Orientation tag.
     *
     * The seven values that mean something other than "already upright":
     *
     * The seven values that mean something other than "already upright", as
     * [rotation in degrees clockwise, IMG_FLIP_* to apply afterwards, 0 for none]:
     *
     *   2 mirror horizontal      5 rotate 270 then mirror horizontal
     *   3 rotate 180             6 rotate 270
     *   4 mirror vertical        7 rotate 90 then mirror horizontal
     *   8 rotate 90
     */
    private const ORIENTATIONS = [
        2 => [0, IMG_FLIP_HORIZONTAL, 0],
        3 => [180, 0, 0],
        4 => [0, 0, IMG_FLIP_VERTICAL],
        5 => [270, IMG_FLIP_HORIZONTAL, 0],
        6 => [270, 0, 0],
        7 => [90, IMG_FLIP_HORIZONTAL, 0],
        8 => [90, 0, 0],
    ];

    private function applyOrientation(\GdImage $image, string $absolutePath): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $exif = @exif_read_data($absolutePath);

        $orientation = is_array($exif) ? ($exif['Orientation'] ?? null) : null;

        if (! is_int($orientation) || ! array_key_exists($orientation, self::ORIENTATIONS)) {
            return $image;
        }

        [$degrees, $flipHorizontal, $flipVertical] = self::ORIENTATIONS[$orientation];

        $result = $degrees === 0 ? $image : imagerotate($image, $degrees, 0);

        // imagerotate returns false on failure, and a sideways photo that fails to
        // straighten is better than one that is not published at all.
        if ($result === false) {
            return $image;
        }

        if ($flipHorizontal !== 0) {
            imageflip($result, $flipHorizontal);
        }

        if ($flipVertical !== 0) {
            imageflip($result, $flipVertical);
        }

        if ($result !== $image) {
            imagedestroy($image);
        }

        return $result;
    }

    /**
     * Resize with a white background, because every output is WebP and WebP does
     * not support transparency here. A PNG logo would otherwise come out with a
     * black box where its transparency used to be.
     */
    private function resize(\GdImage $source, int $width, int $height): \GdImage
    {
        $width = max(1, $width);
        $height = max(1, $height);

        $canvas = imagecreatetruecolor($width, $height);

        $white = imagecolorallocate($canvas, 255, 255, 255);

        if ($white === false) {
            imagedestroy($canvas);

            throw UnprocessableImage::resizeFailed($width, $height);
        }

        imagefilledrectangle($canvas, 0, 0, $width, $height, $white);

        // Resampling on a truecolour source.
        if (! imagecopyresampled($canvas, $source, 0, 0, 0, 0, $width, $height, imagesx($source), imagesy($source))) {
            imagedestroy($canvas);

            throw UnprocessableImage::resizeFailed($width, $height);
        }

        return $canvas;
    }

    /**
     * Encode as WebP. This is also where EXIF stops existing: GD writes no EXIF
     * block, which is what satisfies XC-M2.
     */
    private function encodeWebp(\GdImage $image): string
    {
        ob_start();
        $ok = imagewebp($image, null, (int) config('media.webp_quality'));
        $bytes = (string) ob_get_clean();

        if ($ok === false || $bytes === '') {
            throw UnprocessableImage::encodeFailed();
        }

        return $bytes;
    }

    /**
     * Where a variant's file goes.
     *
     * Keyed by media id, so the path is stable across retries and cannot collide
     * between uploads. The name alone is enough because (media_id, name) is
     * unique, and that index is what stops a retry writing a second file under
     * the same name.
     */
    private function variantPath(Media $media, string $name): string
    {
        return sprintf(
            '%s/%d/%s.webp',
            trim((string) config('media.paths.variants'), '/'),
            $media->getKey(),
            Str::slug($name),
        );
    }

    /**
     * Write or refresh the row for one variant.
     *
     * updateOrCreate rather than create, because the job may be retried and the
     * unique (media_id, name) index would otherwise turn a retry into a crash.
     */
    private function recordVariant(
        Media $media,
        string $name,
        string $path,
        int $width,
        int $height,
        int $fileSize,
    ): MediaVariant {
        $variant = MediaVariant::query()->updateOrCreate(
            ['media_id' => $media->getKey(), 'name' => $name],
            [
                'disk' => (string) config('media.variant_disk'),
                'path' => $path,
                'width' => $width,
                'height' => $height,
                'file_size' => $fileSize,
                'mime_type' => 'image/webp',
            ],
        );

        Log::info('Media variant written.', [
            'media_id' => $media->getKey(),
            'variant' => $name,
            'width' => $width,
            'height' => $height,
            'file_size' => $fileSize,
        ]);

        return $variant;
    }
}
