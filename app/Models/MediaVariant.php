<?php

namespace App\Models;

use Database\Factories\MediaVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One generated size of a Media, always WebP and always EXIF free.
 *
 * @property int $id
 * @property int $media_id
 * @property string $name
 * @property string $disk
 * @property string $path
 * @property int $width
 * @property int $height
 * @property int $file_size
 * @property string $mime_type
 */
#[Fillable([
    'media_id',
    'name',
    'disk',
    'path',
    'width',
    'height',
    'file_size',
    'mime_type',
])]
class MediaVariant extends Model
{
    /** @use HasFactory<MediaVariantFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'width' => 'integer',
            'height' => 'integer',
            'file_size' => 'integer',
        ];
    }
}
