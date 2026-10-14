<?php

namespace App\Http\Resources\Pelayanan;

use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A category as the service form's dropdown needs it: an id and a name.
 *
 * Separate from ServiceCategoryResource because the list wants the service count
 * and a per-row `can` block, and a dropdown wants two fields. Sending the list
 * shape to a <select> is more than the page renders, all of it in the page
 * source.
 *
 * @mixin ServiceCategory
 */
class ServiceOptionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }
}
