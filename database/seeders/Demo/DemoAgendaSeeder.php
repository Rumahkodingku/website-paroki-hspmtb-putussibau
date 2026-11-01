<?php

namespace Database\Seeders\Demo;

use App\Enums\EventStatus;
use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Location;
use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds 4 demo agenda events.
 *
 * Demo-only: called by DemoContentSeeder when config('demo.enabled') is true.
 */
class DemoAgendaSeeder extends Seeder
{
    private const IMAGE = 'illustrations/demo/thumbnail-2-3.png';

    private const MASS_LOCATION_SLUG = 'gereja-paroki';

    public function run(): void
    {
        $media = $this->dummyMedia();
        $imagePath = $media->variantPath($this->largestVariantName());

        $eventCategories = EventCategory::query()->pluck('id', 'slug');

        $timezone = (string) config('app.display_timezone');

        /** @var list<array{slug: string, category: string, title: string, days: int, all_day: bool}> $events */
        $events = [
            [
                'slug' => 'doa-rosario-bersama',
                'category' => 'liturgi',
                'title' => 'Doa Rosario Bersama Umat',
                'days' => 6,
                'all_day' => false,
            ],
            [
                'slug' => 'pembinaan-keluarga-katolik',
                'category' => 'pembinaan',
                'title' => 'Pembinaan Keluarga Katolik',
                'days' => 10,
                'all_day' => false,
            ],
            [
                'slug' => 'kerja-bakti-paroki',
                'category' => 'sosial',
                'title' => 'Kerja Bakti Bersama Paroki',
                'days' => 14,
                'all_day' => true,
            ],
            [
                'slug' => 'rapat-pengurus-paroki',
                'category' => 'rapat',
                'title' => 'Rapat Pengurus Paroki',
                'days' => 21,
                'all_day' => false,
            ],
        ];

        foreach ($events as $event) {
            $categoryId = $eventCategories[$event['category']] ?? null;

            if ($categoryId === null) {
                continue;
            }

            $start = $event['all_day']
                ? Carbon::now($timezone)->addDays($event['days'])->setTime(0, 0)
                : Carbon::now($timezone)->addDays($event['days'])->setTime(9, 0);

            Event::query()->updateOrCreate(
                ['slug' => $event['slug']],
                [
                    'event_category_id' => $categoryId,
                    'title' => $event['title'],
                    'description' => '<p>Kegiatan terbuka untuk seluruh umat paroki. '
                        .'Silakan menghubungi sekretariat untuk informasi lebih lanjut.</p>',
                    'start_at' => $start->utc(),
                    'end_at' => $event['all_day'] ? null : $start->addHours(2)->utc(),
                    'is_all_day' => $event['all_day'],
                    'location_name' => 'Gereja Paroki',
                    'location_id' => Location::query()->where('slug', self::MASS_LOCATION_SLUG)->value('id'),
                    'poster_path' => $imagePath,
                    'poster_alt' => 'Poster kegiatan Paroki',
                    'status' => EventStatus::Scheduled,
                    'is_published' => true,
                    'media_id' => $media->getKey(),
                ],
            );
        }
    }

    private function dummyMedia(): Media
    {
        $source = public_path(self::IMAGE);

        if (! File::exists($source)) {
            throw new \RuntimeException('Berkas '.self::IMAGE.' tidak ditemukan di folder public/.');
        }

        $disk = (string) config('media.original_disk');
        $path = 'media/originals/dummy-agenda.png';

        Storage::disk($disk)->makeDirectory(dirname($path));
        Storage::disk($disk)->put($path, (string) File::get($source));

        $media = Media::query()->updateOrCreate(
            ['path' => $path],
            [
                'disk' => $disk,
                'original_name' => 'thumbnail-2-3.png',
                'mime_type' => 'image/png',
                'file_size' => File::size($source),
                'width' => null,
                'height' => null,
                'alt_text' => 'Gambar contoh untuk pratinjau antarmuka.',
                'status' => MediaStatus::Pending,
                'uploader_id' => null,
                'error_message' => null,
            ],
        );

        GenerateImageVariants::dispatchSync($media->getKey());

        return $media->fresh()->load('variants');
    }

    private function largestVariantName(): string
    {
        /** @var array<string, int> $variants */
        $variants = (array) config('media.variants');

        return (string) collect($variants)->sortDesc()->keys()->first();
    }
}
