<?php

namespace App\Http\Resources\News;

use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An article category as the admin list and the article form need it.
 *
 * Two audiences read this row and they need different things: the list wants to
 * know how many articles are filed under it before letting anybody delete it,
 * and the article form wants only an id and a name. Both are served from one
 * class because the shape is the same, and the count is emitted through
 * whenCounted so a page that did not ask for it gets an absent key rather than
 * a misleading zero — the distinction LocationResource draws for the same
 * reason. See D-39.
 *
 * @mixin PostCategory
 */
class PostCategoryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'sort_order' => $this->sort_order,
            // What stands in the way of a delete, so the dialog can name it
            // before the server refuses. See PostCategoryController::destroy().
            'posts_count' => $this->whenCounted('posts'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
