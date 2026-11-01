<?php

namespace App\Actions\Livestream;

use App\Enums\LivestreamBroadcastStatus;
use App\Models\Livestream;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Ends the live stream.
 *
 * Ending is not the same as publishing a replay. It clears the live slot and
 * records when the broadcast stopped; `is_recording_available` stays false until
 * an admin verifies the YouTube replay and ticks it in the edit form (D-5).
 *
 * @see docs/DECISIONS.md D-101
 */
class EndLivestreamAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Livestream $livestream): Livestream
    {
        return DB::transaction(function () use ($livestream): Livestream {
            $target = Livestream::query()
                ->whereKey($livestream->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($target->broadcast_status !== LivestreamBroadcastStatus::Live) {
                throw ValidationException::withMessages([
                    'broadcast_status' => 'Livestreaming ini tidak sedang live.',
                ]);
            }

            $before = $this->audit->snapshot($target, 'livestreams');

            $target->forceFill([
                'broadcast_status' => LivestreamBroadcastStatus::Ended,
                'ended_at' => now(),
                'recorded_at' => $target->recorded_at ?? now(),
                'live_slot' => null,
            ])->save();

            $this->audit->recordModel(
                'livestreams',
                'broadcast_ended',
                'success',
                $target,
                $before,
                $this->audit->snapshot($target, 'livestreams'),
            );

            return $target;
        });
    }
}
