<?php

namespace App\Http\Resources\Public\Pelayanan;

use App\Models\ServiceFaq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One FAQ as the public accordion needs it.
 *
 * @mixin ServiceFaq
 */
class PublicServiceFaqResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
        ];
    }
}
