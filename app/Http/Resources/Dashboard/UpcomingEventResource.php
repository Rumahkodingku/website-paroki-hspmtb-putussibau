<?php

namespace App\Http\Resources\Dashboard;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An activity as the dashboard's Agenda Terdekat panel needs it.
 *
 * Publication is sent alongside the activity status and never merged into it: a
 * cancelled activity is still published, and the panel labels both so neither
 * fact hides the other (EVT-04, roadmap 9B section 6.7).
 *
 * @mixin Event
 */
class UpcomingEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'is_published' => $this->is_published,
            'start_at' => $this->start_at->toIso8601String(),
            'end_at' => $this->end_at?->toIso8601String(),
            'is_all_day' => $this->is_all_day,
            'location_name' => $this->displayLocationName(),
        ];
    }
}
