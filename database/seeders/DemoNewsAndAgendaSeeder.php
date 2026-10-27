<?php

namespace Database\Seeders;

use App\Enums\EventStatus;
use App\Enums\PostStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Location;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoNewsAndAgendaSeeder extends Seeder
{
    private const MASS_LOCATION_SLUG = 'gereja-paroki';

    public function run(User $author, Media $media, ?string $imagePath): void
    {
        $categories = PostCategory::query()->pluck('id', 'slug');
        $eventCategories = EventCategory::query()->pluck('id', 'slug');

        // Clean up the five posts the previous version of this seeder created,
        // so the total stays at 50 instead of 5 + 50.
        Post::query()->whereIn('slug', [
            'misa-syukur-pesta-pelindung-paroki',
            'kegiatan-bakti-sosial-lingkungan',
            'renungan-pekan-biasa',
            'pengumuman-jadwal-misa-harian',
            'pendaftaran-katekese-anak',
        ])->forceDelete();
        Post::query()->where('slug', 'like', 'demo-berita-%')->forceDelete();

        fake()->seed(20250309);

        $categorySlugs = ['berita', 'kegiatan', 'renungan', 'pengumuman'];
        $scriptures = ['Yohanes 15:13', 'Matius 5:14', 'Roma 8:28', 'Mazmur 23:1', 'Filipi 4:13'];

        for ($i = 0; $i < 50; $i++) {
            $categorySlug = $categorySlugs[$i % count($categorySlugs)];
            $categoryId = $categories[$categorySlug] ?? null;

            if ($categoryId === null) {
                continue;
            }

            $title = (string) Str::title(fake()->unique()->sentence(4));
            $slug = 'demo-berita-'.$i;

            $paragraphs = fake()->paragraphs(random_int(6, 8));
            if (is_string($paragraphs)) {
                $paragraphs = [$paragraphs];
            }

            $paragraphHtml = implode('', array_map(
                static fn (string $paragraph): string => '<p>'.$paragraph.'</p>',
                $paragraphs
            ));

            $words = fake()->words(4);
            if (is_string($words)) {
                $words = [$words];
            }

            $listItems = implode('', array_map(
                static fn (string $item): string => '<li>'.$item.'</li>',
                $words
            ));

            $content = '<h2>'.fake()->sentence(4).'</h2>'
                .$paragraphHtml
                .'<ul>'.$listItems.'</ul>'
                .'<p>'.fake()->paragraph(3).'</p>';

            Post::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'post_category_id' => $categoryId,
                    'author_id' => $author->getKey(),
                    'author_name' => null,
                    'title' => $title,
                    'excerpt' => fake()->sentence(12),
                    'content' => $content,
                    'featured_image' => $imagePath,
                    'image_alt' => 'Ilustrasi kegiatan Paroki',
                    'scripture_reference' => $categorySlug === 'renungan'
                        ? $scriptures[array_rand($scriptures)]
                        : null,
                    'status' => PostStatus::Published,
                    'published_at' => Carbon::now()->subDays(random_int(0, 60))->utc(),
                    'is_pinned' => $i < 5,
                    'view_count' => random_int(20, 400),
                    'meta_title' => null,
                    'meta_description' => null,
                    'media_id' => $media->getKey(),
                ],
            );
        }

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
}
