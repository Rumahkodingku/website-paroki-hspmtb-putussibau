<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The metadata an editor wrote for one static page.
 *
 * There is one row per allowed page_key, and the key list lives in
 * config/seo.php. The row is optional: no row means the page falls back to its
 * configured default and then to the global SEO settings.
 *
 * @property int $id
 * @property string $page_key
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property int|null $og_media_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Media|null $media
 */
#[Fillable([
    'page_key',
    'meta_title',
    'meta_description',
    'og_media_id',
])]
class PageSeo extends Model
{
    protected $table = 'page_seo';

    /**
     * @return BelongsTo<Media, $this>
     */
    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'og_media_id');
    }

    public function ogImageUrl(): ?string
    {
        return $this->media?->url('medium');
    }
}
