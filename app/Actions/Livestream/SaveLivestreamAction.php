<?php

namespace App\Actions\Livestream;

use App\Enums\LivestreamBroadcastStatus;
use App\Enums\LivestreamEditorialStatus;
use App\Models\Livestream;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\YoutubeEmbed;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Writes a livestream record.
 *
 * It is an Action rather than two lines in the controller because four things
 * happen together and three of them are derived:
 *
 *   1. `youtube_video_id` is extracted from the URL server-side, so a browser
 *      cannot point the embed at a different video;
 *
 *   2. `published_at` is stamped the first time the record becomes published,
 *      and left alone afterwards — a record keeps the date it was first shown;
 *
 *   3. `recorded_at` defaults to the end time when an admin marks a replay
 *      available, so the archive cannot get an undated public item;
 *
 *   4. the write, the actor columns and the audit event run in one transaction.
 *
 * It also enforces the editorial half of the live invariant: a live record may
 * not be unpublished or archived through the ordinary form. The admin has to
 * end the stream first, which is a deliberate two-step operation rather than a
 * silent disappearance from the homepage.
 *
 * @see docs/DECISIONS.md D-101
 */
class SaveLivestreamAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes  the validated payload
     */
    public function handle(Livestream $livestream, array $attributes, User $actor): Livestream
    {
        return DB::transaction(function () use ($livestream, $attributes, $actor): Livestream {
            $isNew = ! $livestream->exists;
            $before = $isNew ? [] : $this->audit->snapshot($livestream, 'livestreams');
            $statusBefore = $livestream->status;

            $videoId = YoutubeEmbed::id(is_string($attributes['youtube_url'] ?? null) ? $attributes['youtube_url'] : null);

            if ($videoId === null) {
                throw ValidationException::withMessages([
                    'youtube_url' => 'Tautan video harus berupa tautan YouTube yang valid.',
                ]);
            }

            $status = LivestreamEditorialStatus::from((string) $attributes['status']);

            if (
                ! $isNew
                && $livestream->broadcast_status === LivestreamBroadcastStatus::Live
                && $status !== LivestreamEditorialStatus::Published
            ) {
                throw ValidationException::withMessages([
                    'status' => 'Akhiri siaran terlebih dahulu sebelum mengubah status publikasi.',
                ]);
            }

            $attributes['youtube_video_id'] = $videoId;
            $attributes['published_at'] = $this->resolvePublishedAt($livestream, $status);

            if ((bool) ($attributes['is_recording_available'] ?? false) && empty($attributes['recorded_at'])) {
                $attributes['recorded_at'] = $livestream->ended_at ?? now();
            }

            if ($isNew) {
                $attributes['created_by'] = $actor->getKey();
            }

            $attributes['updated_by'] = $actor->getKey();

            $livestream->fill($attributes);
            $livestream->save();

            if ($isNew) {
                $this->audit->created('livestreams', $livestream);
            } else {
                $this->audit->updated(
                    'livestreams',
                    $livestream,
                    $before,
                    $this->auditAction($statusBefore, $status),
                );
            }

            return $livestream;
        });
    }

    /**
     * Set once, on the first publication.
     *
     * Reading published_at from the model *before* fill() is what makes this
     * "once": a re-save while already published sees the date it was given the
     * first time and keeps it, and unpublishing does not clear it.
     */
    private function resolvePublishedAt(Livestream $livestream, LivestreamEditorialStatus $status): mixed
    {
        if ($status === LivestreamEditorialStatus::Published && $livestream->published_at === null) {
            return now();
        }

        return $livestream->published_at;
    }

    private function auditAction(
        ?LivestreamEditorialStatus $before,
        LivestreamEditorialStatus $after,
    ): string {
        if ($before === $after) {
            return 'updated';
        }

        return match ($after) {
            LivestreamEditorialStatus::Published => 'published',
            LivestreamEditorialStatus::Archived => 'archived',
            LivestreamEditorialStatus::Draft => 'unpublished',
        };
    }
}
