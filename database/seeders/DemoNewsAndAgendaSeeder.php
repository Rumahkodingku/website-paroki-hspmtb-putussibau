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

class DemoNewsAndAgendaSeeder extends Seeder
{
    private const MASS_LOCATION_SLUG = 'gereja-paroki';

    public function run(User $author, Media $media, ?string $imagePath): void
    {
        $categories = PostCategory::query()->pluck('id', 'slug');
        $eventCategories = EventCategory::query()->pluck('id', 'slug');

        $content = '<p>Paroki menggelar kegiatan bersama umat pada pekan ini. '
            .'Kegiatan diisi dengan doa, sharing, dan ramah tamah.</p>'
            .'<h2>Rangkaian kegiatan</h2>'
            .'<ul><li>Doa pembuka</li><li>Renungan singkat</li><li>Ramah tamah</li></ul>'
            .'<p>Seluruh umat diundang untuk hadir dan membawa keluarga.</p>';

        /** @var list<array{slug: string, category: string, title: string, excerpt: string, days: int, pinned: bool, scripture: ?string}> $posts */
        $posts = [
            [
                'slug' => 'misa-syukur-pesta-pelindung-paroki',
                'category' => 'berita',
                'title' => 'Misa Syukur Pesta Pelindung Paroki',
                'excerpt' => 'Umat diajak hadir dalam misa syukur tahunan pesta pelindung paroki.',
                'days' => 2,
                'pinned' => true,
                'scripture' => null,
            ],
            [
                'slug' => 'kegiatan-bakti-sosial-lingkungan',
                'category' => 'kegiatan',
                'title' => 'Kegiatan Bakti Sosial Lingkungan',
                'excerpt' => 'Aksi bakti sosial bersama warga di sekitar lingkungan gereja.',
                'days' => 5,
                'pinned' => false,
                'scripture' => null,
            ],
            [
                'slug' => 'renungan-pekan-biasa',
                'category' => 'renungan',
                'title' => 'Renungan Pekan Biasa: Hidup dalam Kasih',
                'excerpt' => 'Bacaan pekan ini mengajak umat menghidupi kasih dalam perbuatan nyata.',
                'days' => 7,
                'pinned' => false,
                'scripture' => 'Yohanes 15:13',
            ],
            [
                'slug' => 'pengumuman-jadwal-misa-harian',
                'category' => 'pengumuman',
                'title' => 'Pengumuman Jadwal Misa Harian',
                'excerpt' => 'Terjadi penyesuaian jam misa harian selama pekan ini.',
                'days' => 9,
                'pinned' => false,
                'scripture' => null,
            ],
            [
                'slug' => 'pendaftaran-katekese-anak',
                'category' => 'pengumuman',
                'title' => 'Pendaftaran Katekese Anak Dibuka',
                'excerpt' => 'Pendaftaran katekese untuk anak dibuka di sekretariat paroki.',
                'days' => 12,
                'pinned' => false,
                'scripture' => null,
            ],
        ];

        foreach ($posts as $post) {
            $categoryId = $categories[$post['category']] ?? null;

            if ($categoryId === null) {
                continue;
            }

            Post::query()->updateOrCreate(
                ['slug' => $post['slug']],
                [
                    'post_category_id' => $categoryId,
                    'author_id' => $author->getKey(),
                    'author_name' => null,
                    'title' => $post['title'],
                    'excerpt' => $post['excerpt'],
                    'content' => $content,
                    'featured_image' => $imagePath,
                    'image_alt' => 'Ilustrasi kegiatan Paroki',
                    'scripture_reference' => $post['scripture'],
                    'status' => PostStatus::Published,
                    'published_at' => Carbon::now()->subDays($post['days'])->utc(),
                    'is_pinned' => $post['pinned'],
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
