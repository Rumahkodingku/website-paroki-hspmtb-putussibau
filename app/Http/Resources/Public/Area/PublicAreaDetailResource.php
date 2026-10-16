<?php

namespace App\Http\Resources\Public\Area;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An area as the public detail page needs it.
 *
 * The leader and phone are omitted unless the area allows its contact (XC-P1,
 * D-57). The children (lingkungan under a wilayah) are listed by name only.
 *
 * @mixin Area
 */
class PublicAreaDetailResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type->value,
            'type_label' => $this->type->label(),
            'description' => $this->description,
            /*
             * The address, and the finished map link rather than the raw
             * coordinates (D-61). Same as PublicLocationResource: Area::mapsUrl()
             * decides the fallback order on the server, so React never has to
             * know whether a coordinate pair exists. The pair itself is not
             * personal data, but it is not needed by the page either.
             */
            'address' => $this->address,
            'mapsUrl' => $this->mapsUrl(),
            'parent' => $this->whenLoaded('parent', fn (): ?array => $this->parent === null ? null : [
                'id' => $this->parent->getKey(),
                'name' => $this->parent->name,
                'slug' => $this->parent->slug,
                'type' => $this->parent->type->value,
                'type_label' => $this->parent->type->label(),
            ]),
            'children' => $this->whenLoaded('children', fn (): array => $this->children
                ->map(fn (Area $child): array => [
                    'id' => $child->getKey(),
                    'name' => $child->name,
                    'slug' => $child->slug,
                    'type' => $child->type->value,
                    'type_label' => $child->type->label(),
                    'leader_name' => $child->show_contact ? $child->leader_name : null,
                    'contact_phone' => $child->show_contact ? $child->contact_phone : null,
                ])
                ->values()
                ->all()),
        ];

        if ($this->show_contact) {
            $data['leader_name'] = $this->leader_name;
            $data['contact_phone'] = $this->contact_phone;
        }

        return $data;
    }
}
