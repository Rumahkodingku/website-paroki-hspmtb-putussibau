<?php

namespace App\Models;

use Database\Factories\PostCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * A bucket an article is filed under: Berita, Kegiatan, Renungan, Pengumuman.
 *
 * Only the four PRD names exist today and they are seeded, but nothing stops the
 * parish adding a fifth from the admin interface — hence sort_order being a real
 * column rather than an alphabetical accident. "Renungan" has to be able to sit
 * where the parish wants it, because it drives the scripture field.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $sort_order
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Post> $posts
 */
#[Fillable(['name', 'slug', 'sort_order'])]
class PostCategory extends Model
{
    /** @use HasFactory<PostCategoryFactory> */
    use HasFactory;

    /**
     * Used by the "still in use" check before a delete, and by the admin list's
     * article count.
     *
     * @return HasMany<Post, $this>
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
