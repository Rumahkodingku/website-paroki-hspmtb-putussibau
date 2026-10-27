<?php

namespace App\Models;

use Database\Factories\ParishProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The parish profile content — a singleton row.
 *
 * Identity (name, logo, contact) stays in site settings; this row holds only
 * the editorial content shown on /profil: the long description, the founding
 * year, the cover image, and the profile video.
 *
 * `description` is sanitized HTML, rendered through components/rich-text.tsx.
 *
 * @property int $id
 * @property string|null $description
 * @property int|null $established_year
 * @property int|null $cover_media_id
 * @property string|null $cover_alt
 * @property string|null $video_url
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Media|null $coverMedia
 *
 * @see docs/DECISIONS.md D-78
 */
#[Fillable(['description', 'established_year', 'cover_media_id', 'cover_alt', 'video_url'])]
class ParishProfile extends Model
{
    /** @use HasFactory<ParishProfileFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Media, $this>
     */
    public function coverMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_media_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['established_year' => 'integer'];
    }

    /**
     * The one row, or an empty instance when none exists yet. No write on read.
     */
    public static function current(): self
    {
        return static::query()->firstOrNew([]);
    }
}
