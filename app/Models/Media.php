<?php

namespace App\Models;

use App\Enums\MediaStatus;
use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/**
 * One uploaded image, before and after processing.
 *
 * The row exists as soon as the original is stored, with width and height still
 * null, because XC-M1 has the admin UI show processing state and something has
 * to be read.
 *
 * @property int $id
 * @property string $disk
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $file_size
 * @property int|null $width
 * @property int|null $height
 * @property string|null $alt_text
 * @property MediaStatus $status
 * @property int|null $uploader_id
 * @property string|null $error_message
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, MediaVariant> $variants
 */
#[Fillable([
    'disk',
    'path',
    'original_name',
    'mime_type',
    'file_size',
    'width',
    'height',
    'alt_text',
    'status',
    'uploader_id',
    'error_message',
])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploader_id');
    }

    /**
     * @return HasMany<MediaVariant, $this>
     */
    public function variants(): HasMany
    {
        return $this->hasMany(MediaVariant::class);
    }

    /**
     * The browser URL of a variant.
     *
     * Passing no name looks up the original, which lives on a private disk and
     * normally has no public URL, so that returns null. That is the intent:
     * only processed variants are meant to be reachable.
     */
    public function url(?string $variant = null): ?string
    {
        $target = $variant === null
            ? ['disk' => $this->disk, 'path' => $this->path]
            : $this->variants->firstWhere('name', $variant)?->only(['disk', 'path']);

        if ($target === null) {
            return null;
        }

        // Storage::disk()->url() happily builds a /storage path for a disk that
        // is not actually served, so the visibility setting has to be consulted
        // directly. Without this the original, which lives on a private disk, gets
        // a URL that renders as a broken image instead of nothing at all.
        if (config("filesystems.disks.{$target['disk']}.visibility") !== 'public') {
            return null;
        }

        return Storage::disk($target['disk'])->url($target['path']);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => MediaStatus::class,
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * True while variants are still expected.
     */
    public function isPending(): bool
    {
        return in_array($this->status, [MediaStatus::Pending, MediaStatus::Processing], true);
    }
}
