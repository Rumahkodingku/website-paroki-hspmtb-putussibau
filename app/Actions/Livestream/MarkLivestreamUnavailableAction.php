<?php

namespace App\Actions\Livestream;

use App\Enums\LivestreamBroadcastStatus;
use App\Models\Livestream;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Marks a broadcast unavailable because YouTube cannot play it.
 *
 * A live stream has to be ended first: the admin should record the end time
 * before saying the video is gone. Once unavailable, the record is excluded
 * from both the homepage and the archive until an admin fixes the URL and
 * starts it again (only if it never ended) or creates a new record.
 *
 * @see docs/DECISIONS.md D-101
 */
class MarkLivestreamUnavailableAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Livestream $livestream): Livestream
    {
        return DB::transaction(function () use ($livestream): Livestream {
            $target = Livestream::query()
                ->whereKey($livestream->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($target->broadcast_status === LivestreamBroadcastStatus::Live) {
                throw ValidationException::withMessages([
                    'broadcast_status' => 'Akhiri siaran terlebih dahulu sebelum menandainya tidak tersedia.',
                ]);
            }

            if ($target->broadcast_status === LivestreamBroadcastStatus::Unavailable) {
                return $target;
            }

            $before = $this->audit->snapshot($target, 'livestreams');

            $target->forceFill([
                'broadcast_status' => LivestreamBroadcastStatus::Unavailable,
                'live_slot' => null,
            ])->save();

            $this->audit->recordModel(
                'livestreams',
                'broadcast_unavailable',
                'success',
                $target,
                $before,
                $this->audit->snapshot($target, 'livestreams'),
            );

            return $target;
        });
    }
}
