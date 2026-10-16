<?php

namespace App\Http\Resources\Public\Area;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An area as the public directory card needs it.
 *
 * The leader and phone are omitted unless the area allows its contact (XC-P1,
 * D-57): the rule is enforced here on the way out, never in React.
 *
 * @mixin Area
 */
class PublicAreaCardResource extends JsonResource
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
        ];

        if ($this->show_contact) {
            $data['leader_name'] = $this->leader_name;
            $data['contact_phone'] = $this->contact_phone;
        }

        return $data;
    }
}
