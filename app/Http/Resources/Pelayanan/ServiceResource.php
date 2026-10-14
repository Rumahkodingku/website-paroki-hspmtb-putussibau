<?php

namespace App\Http\Resources\Pelayanan;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A service as the edit form needs it: every field, plus its category.
 *
 * The children (requirements, steps, FAQs) are sent as separate props by the
 * controller rather than nested here. A non-paginated Resource::collection
 * serializes to `{data: [...]}` (D-39), so the controller resolves each list with
 * array_values(...->resolve()) and the tabs receive a plain array.
 *
 * `introduction` is sanitized HTML, rendered only through RichText.
 *
 * @mixin Service
 */
class ServiceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'service_category_id' => $this->service_category_id,
            'thumbnail_media_id' => $this->thumbnail_media_id,
            'thumbnail_alt' => $this->thumbnail_alt,
            'thumbnail_url' => $this->whenLoaded('thumbnailMedia', fn (): ?string => $this->thumbnailMedia->url('medium')),
            'summary' => $this->summary,
            'introduction' => $this->introduction,
            'schedule_info' => $this->schedule_info,
            'fee_info' => $this->fee_info,
            'contact_name' => $this->contact_name,
            'contact_phone' => $this->contact_phone,
            'form_url' => $this->form_url,
            'form_button_label' => $this->form_button_label,
            'is_form_open' => $this->is_form_open,
            'form_closed_message' => $this->form_closed_message,
            'is_emergency' => $this->is_emergency,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
            'updated_at' => $this->updated_at->toIso8601String(),
            'category' => $this->whenLoaded('serviceCategory', fn (): array => [
                'id' => $this->serviceCategory->getKey(),
                'name' => $this->serviceCategory->name,
            ]),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
            ],
        ];
    }
}
