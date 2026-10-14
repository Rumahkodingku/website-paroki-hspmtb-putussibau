<?php

namespace App\Http\Resources\Pelayanan;

use App\Models\ServiceFaq;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One FAQ as the admin list and the public accordion need it.
 *
 * @mixin ServiceFaq
 */
class ServiceFaqResource extends JsonResource
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
            'sort_order' => $this->sort_order,
        ];
    }
}
