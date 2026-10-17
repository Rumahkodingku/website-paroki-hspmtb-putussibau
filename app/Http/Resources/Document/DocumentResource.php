<?php

namespace App\Http\Resources\Document;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Document
 */
class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'document_category_id' => $this->document_category_id,
            'description' => $this->description,
            'original_filename' => $this->original_filename,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension(),
            'file_size' => $this->file_size,
            'version_label' => $this->version_label,
            'document_date' => $this->document_date?->toDateString(),
            'expires_at' => $this->expires_at?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_featured' => $this->is_featured,
            'is_expired' => $this->isExpired(),
            'download_count' => $this->download_count,
            'published_at' => $this->published_at?->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'category' => $this->whenLoaded('category', fn (): array => [
                'id' => $this->category->getKey(),
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ]),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                'preview' => $request->user()?->can('view', $this->resource) ?? false,
            ],
        ];
    }
}
