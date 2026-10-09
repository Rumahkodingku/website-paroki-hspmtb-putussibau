<?php

namespace App\Http\Resources\Public\MassSchedule;

use App\Models\ScheduleNotice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A notice as a visitor sees it.
 *
 * Only general notices and notices matching the active filter reach the public
 * page — ScheduleNotice::scopeCurrentlyVisible() selects them, and the
 * controller narrows by target. title and message are the two fields the banner
 * renders, so they are the two fields sent.
 *
 * @mixin ScheduleNotice
 */
class PublicScheduleNoticeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'is_general' => $this->isGeneral(),
        ];
    }
}
