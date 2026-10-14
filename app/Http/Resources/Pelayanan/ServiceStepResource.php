<?php

namespace App\Http\Resources\Pelayanan;

use App\Models\ServiceStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One procedure step as the admin list and the public numbered list need it.
 *
 * @mixin ServiceStep
 */
class ServiceStepResource extends JsonResource
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
            'sort_order' => $this->sort_order,
        ];
    }
}
