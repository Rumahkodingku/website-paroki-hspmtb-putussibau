<?php

namespace App\Models;

use Database\Factories\ParishServiceAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The service-area narrative — a singleton row.
 *
 * One narrative plus an optional map. There is deliberately no hierarchy of
 * wilayah/stasi/lingkungan here: that domain belongs to the Communities module
 * in Phase 7 and is only displayed on /profil/wilayah (D-52, roadmap 6.6).
 *
 * `content` is sanitized HTML, rendered through components/rich-text.tsx.
 *
 * @property int $id
 * @property string|null $content
 * @property int|null $map_media_id
 * @property string|null $map_url
 * @property bool $is_published
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Media|null $mapMedia
 */
#[Fillable(['content', 'map_media_id', 'map_url', 'is_published'])]
class ParishServiceArea extends Model
{
    /** @use HasFactory<ParishServiceAreaFactory> */
    use HasFactory;

    /**
     * @return BelongsTo<Media, $this>
     */
    public function mapMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'map_media_id');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    /**
     * The one row, or an empty instance when none exists yet. No write on read.
     */
    public static function current(): self
    {
        return static::query()->firstOrNew([]);
    }

    /**
     * @param  Builder<ParishServiceArea>  $query
     * @return Builder<ParishServiceArea>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }
}
