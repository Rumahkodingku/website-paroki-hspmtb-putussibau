<?php

namespace Database\Seeders\Demo;

use App\Enums\LivestreamBroadcastStatus;
use App\Enums\LivestreamCategory;
use App\Enums\LivestreamEditorialStatus;
use App\Models\Livestream;
use App\Models\Location;
use App\Models\User;
use App\Support\YoutubeEmbed;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Seeds the livestream that appears in the homepage live section.
 *
 * The broadcast status is manual in this application, so the demo record is
 * published and live immediately. It never ends another live stream: if an
 * admin (or another seeder) already has one running, this seeder warns and
 * leaves the existing stream alone.
 *
 * Idempotent: keyed by the extracted YouTube video id.
 *
 * Demo-only: called by DemoContentSeeder when config('demo.enabled') is true.
 */
class DemoLivestreamingSeeder extends Seeder
{
    private const YOUTUBE_URL = 'https://www.youtube.com/live/PDDOkUq33Sw';

    public function run(): void
    {
        $videoId = YoutubeEmbed::id(self::YOUTUBE_URL);

        if ($videoId === null) {
            throw new RuntimeException('URL YouTube demo tidak menghasilkan video id yang valid.');
        }

        $currentLive = Livestream::currentLive();
        $demo = Livestream::withTrashed()
            ->where('youtube_video_id', $videoId)
            ->first();

        if ($currentLive !== null && ($demo === null || ! $currentLive->is($demo))) {
            $this->command->warn(
                'DemoLivestreamingSeeder dilewati: sudah ada siaran live lain. '
                .'Live yang ada tidak diakhiri.'
            );

            return;
        }

        $locationId = Location::query()->where('slug', 'gereja-paroki')->value('id')
            ?? Location::query()->active()->orderBy('id')->value('id');
        $authorId = User::query()
            ->where('email', 'redaksi-demo@example.test')
            ->value('id');

        $demo ??= new Livestream;

        if ($demo->trashed()) {
            $demo->restore();
        }

        $demo->fill([
            'title' => 'Misa Live Demo',
            'description' => 'Siaran langsung contoh untuk pratinjau antarmuka.',
            'youtube_video_id' => $videoId,
            'youtube_url' => self::YOUTUBE_URL,
            'location_id' => $locationId,
            'category' => LivestreamCategory::Liturgi,
            'status' => LivestreamEditorialStatus::Published,
            'scheduled_at' => Carbon::now(),
            'started_at' => Carbon::now(),
            'ended_at' => null,
            'recorded_at' => null,
            'is_recording_available' => false,
            'published_at' => $demo->published_at ?? now(),
            'created_by' => $demo->created_by ?? $authorId,
            'updated_by' => $authorId,
        ]);

        $demo->forceFill([
            'broadcast_status' => LivestreamBroadcastStatus::Live,
            'live_slot' => 1,
        ])->save();
    }
}
