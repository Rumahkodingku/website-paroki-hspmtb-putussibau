<?php

namespace App\Http\Resources\Public\Pelayanan;

use App\Models\ServiceStep;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One procedure step as the public numbered list needs it.
 *
 * @mixin ServiceStep
 */
class PublicServiceStepResource extends JsonResource
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
        ];
    }
}
