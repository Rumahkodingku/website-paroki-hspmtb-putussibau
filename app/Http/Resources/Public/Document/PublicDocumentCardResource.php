<?php

namespace App\Http\Resources\Public\Document;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class PublicDocumentCardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->getKey(),
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'extension' => $this->extension(),
            'file_size' => $this->file_size,
            'version_label' => $this->version_label,
            'document_date' => $this->document_date?->toDateString(),
            'is_featured' => $this->is_featured,
            'is_expired' => $this->isExpired(),
            'is_pdf' => $this->isPdf(),
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
