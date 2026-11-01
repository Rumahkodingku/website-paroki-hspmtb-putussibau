<?php

namespace App\Actions\Livestream;

use App\Enums\LivestreamBroadcastStatus;
use App\Enums\LivestreamEditorialStatus;
use App\Models\Livestream;
use App\Services\AuditLogger;
use App\Support\YoutubeEmbed;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Marks a stream live and ends whichever stream was live before it.
 *
 * The rule is one live stream per parish. It is enforced twice on purpose:
 *
 *   - a transaction locks the current slot row before changing it, so two
 *     admins changing an existing live stream cannot interleave;
 *
 *   - `livestreams.live_slot` has a unique index. Two requests starting from an
 *     empty live set both see no row to lock, both try to claim slot 1, and the
 *     database rejects the second. A transaction alone cannot do that.
 *
 * The duplicate-key failure is translated into a form error rather than a 500,
 * because the admin can retry after reloading.
 *
 * @see docs/DECISIONS.md D-101
 */
class StartLivestreamAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Livestream $livestream): Livestream
    {
        try {
            return DB::transaction(function () use ($livestream): Livestream {
                $target = Livestream::query()
                    ->whereKey($livestream->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($target->status !== LivestreamEditorialStatus::Published) {
                    throw ValidationException::withMessages([
                        'broadcast_status' => 'Terbitkan livestreaming ini sebelum memulai siaran.',
                    ]);
                }

                // Idempotent: pressing the button twice should not create a
                // second audit event or change the start time.
                if ($target->broadcast_status === LivestreamBroadcastStatus::Live) {
                    return $target;
                }

                $startable = $target->broadcast_status === LivestreamBroadcastStatus::Scheduled
                    || (
                        $target->broadcast_status === LivestreamBroadcastStatus::Unavailable
                        && $target->ended_at === null
                    );

                if (! $startable) {
                    throw ValidationException::withMessages([
                        'broadcast_status' => 'Siaran ini sudah berakhir. Buat record baru untuk siaran berikutnya.',
                    ]);
                }

                if (YoutubeEmbed::id($target->youtube_url) === null) {
                    throw ValidationException::withMessages([
                        'youtube_url' => 'Perbaiki URL YouTube sebelum memulai siaran.',
                    ]);
                }

                $previous = Livestream::query()
                    ->whereNotNull('live_slot')
                    ->whereKeyNot($target->getKey())
                    ->lockForUpdate()
                    ->get();

                foreach ($previous as $old) {
                    $before = $this->audit->snapshot($old, 'livestreams');

                    $old->forceFill([
                        'broadcast_status' => LivestreamBroadcastStatus::Ended,
                        'ended_at' => now(),
                        // The replay is not assumed to exist. The admin marks
                        // is_recording_available after checking YouTube.
                        'recorded_at' => $old->recorded_at ?? now(),
                        'live_slot' => null,
                    ])->save();

                    $this->audit->recordModel(
                        'livestreams',
                        'broadcast_ended',
                        'success',
                        $old,
                        $before,
                        $this->audit->snapshot($old, 'livestreams'),
                        ['replaced_by' => $target->getKey()],
                    );
                }

                $before = $this->audit->snapshot($target, 'livestreams');

                $target->forceFill([
                    'broadcast_status' => LivestreamBroadcastStatus::Live,
                    'started_at' => now(),
                    'ended_at' => null,
                    'live_slot' => 1,
                ])->save();

                $this->audit->recordModel(
                    'livestreams',
                    'broadcast_started',
                    'success',
                    $target,
                    $before,
                    $this->audit->snapshot($target, 'livestreams'),
                    ['previous_live_id' => $previous->first()?->getKey()],
                );

                return $target;
            });
        } catch (QueryException $exception) {
            if ((int) ($exception->errorInfo[1] ?? 0) === 1062) {
                throw ValidationException::withMessages([
                    'broadcast_status' => 'Siaran lain baru saja dimulai. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            throw $exception;
        }
    }
}
