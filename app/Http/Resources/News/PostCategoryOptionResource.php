<?php

namespace App\Http\Resources\News;

use App\Models\PostCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An article category as the article form's dropdown needs it.
 *
 * Separate from PostCategoryResource because the admin list wants the article
 * count and the per-row `can` block, and a dropdown wants two fields. Sending
 * the list shape to a <select> is the same mistake as sending the list shape to
 * a visitor: more than the page renders, all of it in the page source.
 *
 * @mixin PostCategory
 */
class PostCategoryOptionResource extends JsonResource
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
        ];
    }
}
