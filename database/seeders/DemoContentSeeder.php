<?php

namespace Database\Seeders;

use App\Enums\DayOfWeek;
use App\Enums\EventStatus;
use App\Enums\LocationType;
use App\Enums\MediaStatus;
use App\Enums\PastorPosition;
use App\Enums\PostStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Location;
use App\Models\MassSchedule;
use App\Models\Media;
use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use App\Models\ParishHistoryItem;
use App\Models\ParishProfile;
use App\Models\ParishServiceArea;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\ScheduleNotice;
use App\Models\SpecialMass;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Sample content so every screen can be looked at without filling it by hand.
 *
 * This seeder is called from DatabaseSeeder, which is a deliberate divergence
 * from D-14 (that decision emptied DatabaseSeeder of fabricated data). It was
 * taken at the project owner's request so `migrate:fresh --seed` produces a
 * browsable site. The content is sample data and is NOT parish data; every row
 * is created with updateOrCreate keyed on a stable value, so running the seeder
 * again edits rather than duplicates.
 *
 * `app:e2e:prepare` turns `demo.enabled` off before it runs
 * `migrate:fresh --seed`, so the Playwright database is rebuilt bare and the
 * suite stays deterministic. See config/demo.php.
 *
 * Images: every image slot points at one media row built from
 * `public/images.jpg`, passed through the real ImageProcessor pipeline so the
 * variants and URLs are exactly what an uploaded image would produce.
 *
 * @see docs/DECISIONS.md D-14, D-53
 */
class DemoContentSeeder extends Seeder
{
    /**
     * The file every image slot uses. Landscape, so portrait slots (pastors,
     * board members) will crop rather than frame — fine for a preview.
     */
    private const IMAGE = 'images.jpg';

    private const MASS_LOCATION_SLUG = 'gereja-paroki';

    public function run(): void
    {
        if (! config('demo.enabled')) {
            $this->command->warn('DemoContentSeeder dilewati: config demo.enabled = false.');

            return;
        }

        $media = $this->dummyMedia();
        $imagePath = $media->variantPath($this->largestVariantName());
        $author = $this->demoAuthor();

        $this->seedMassSchedules();
        $this->seedNewsAndAgenda($author, $media, $imagePath);
        $this->seedParishProfile($media, $imagePath);

        $this->command->info('Data contoh Profil Paroki, Jadwal Misa, Berita & Agenda dimuat.');
    }

    /**
     * Copy public/images.jpg into the media pipeline and process it.
     *
     * Idempotent on the original path: the same row is reused on every run, and
     * the job is re-run so the variants on disk always match the row.
     */
    private function dummyMedia(): Media
    {
        $source = public_path(self::IMAGE);

        if (! File::exists($source)) {
            throw new RuntimeException('Berkas '.self::IMAGE.' tidak ditemukan di folder public/.');
        }

        $disk = (string) config('media.original_disk');
        $path = 'media/originals/dummy-paroki.jpg';

        Storage::disk($disk)->makeDirectory(dirname($path));
        Storage::disk($disk)->put($path, (string) File::get($source));

        $media = Media::query()->updateOrCreate(
            ['path' => $path],
            [
                'disk' => $disk,
                'original_name' => 'dummy-paroki.jpg',
                'mime_type' => 'image/jpeg',
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

    /**
     * The widest configured variant name, so stored paths match what a real
     * upload writes.
     */
    private function largestVariantName(): string
    {
        /** @var array<string, int> $variants */
        $variants = (array) config('media.variants');

        return (string) collect($variants)->sortDesc()->keys()->first();
    }

    /**
     * An account the seeded articles can point at. `posts.author_id` is a
     * required foreign key, so there has to be a user.
     */
    private function demoAuthor(): User
    {
        $author = User::query()->updateOrCreate(
            ['email' => 'redaksi-demo@example.test'],
            [
                'name' => 'Redaksi Contoh',
                'password' => Hash::make(Str::random(40)),
            ],
        );

        // is_active is deliberately not fillable (D-13), so it is set directly.
        $author->forceFill([
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        return $author;
    }

    /**
     * Phase 04 — locations, weekly masses, special masses, one notice.
     */
    private function seedMassSchedules(): void
    {
        $paroki = Location::query()->updateOrCreate(
            ['slug' => self::MASS_LOCATION_SLUG],
            [
                'name' => 'Gereja Paroki Hati Santa Perawan Maria Tak Bernoda',
                'type' => LocationType::GerejaParoki,
                'address' => 'Putussibau, Kabupaten Kapuas Hulu',
                'latitude' => null,
                'longitude' => null,
                'maps_url' => null,
                'notes' => null,
                'is_active' => true,
            ],
        );

        $kapel = Location::query()->updateOrCreate(
            ['slug' => 'kapel-santo-paulus'],
            [
                'name' => 'Kapel Santo Paulus',
                'type' => LocationType::Kapel,
                'address' => null,
                'latitude' => null,
                'longitude' => null,
                'maps_url' => null,
                'notes' => null,
                'is_active' => true,
            ],
        );

        $stasi = Location::query()->updateOrCreate(
            ['slug' => 'stasi-santo-yosef'],
            [
                'name' => 'Stasi Santo Yosef',
                'type' => LocationType::Stasi,
                'address' => null,
                'latitude' => null,
                'longitude' => null,
                'maps_url' => null,
                'notes' => null,
                'is_active' => true,
            ],
        );

        /** @var list<array{0: Location, 1: DayOfWeek, 2: string, 3: string}> $schedules */
        $schedules = [
            [$paroki, DayOfWeek::Minggu, '06:30:00', 'Misa Minggu Pagi'],
            [$paroki, DayOfWeek::Minggu, '09:00:00', 'Misa Minggu Kedua'],
            [$paroki, DayOfWeek::Minggu, '17:30:00', 'Misa Minggu Sore'],
            [$paroki, DayOfWeek::Sabtu, '17:30:00', 'Misa Sabtu Sore'],
            [$paroki, DayOfWeek::Senin, '05:30:00', 'Misa Harian'],
            [$paroki, DayOfWeek::Rabu, '05:30:00', 'Misa Harian'],
            [$paroki, DayOfWeek::Jumat, '05:30:00', 'Misa Harian'],
            [$kapel, DayOfWeek::Rabu, '18:00:00', 'Misa Lingkungan'],
            [$stasi, DayOfWeek::Jumat, '18:30:00', 'Misa Stasi'],
        ];

        foreach ($schedules as [$location, $day, $time, $name]) {
            MassSchedule::query()->updateOrCreate(
                [
                    'location_id' => $location->getKey(),
                    'day_of_week' => $day,
                    'start_time' => $time,
                ],
                ['name' => $name, 'notes' => null, 'is_active' => true],
            );
        }

        $specialStart = Carbon::now((string) config('app.display_timezone'))->addDays(20)->setTime(9, 0);

        SpecialMass::query()->updateOrCreate(
            ['title' => 'Misa Syukur Pesta Pelindung Paroki'],
            [
                'location_id' => $paroki->getKey(),
                'description' => 'Misa syukur tahunan pesta pelindung paroki, diikuti ramah tamah umat.',
                'start_at' => $specialStart->utc(),
                'end_at' => $specialStart->addHours(2)->utc(),
                'notes' => null,
                'is_active' => true,
            ],
        );

        ScheduleNotice::query()->updateOrCreate(
            ['title' => 'Penyesuaian jadwal misa harian'],
            [
                'message' => 'Misa harian pekan ini dimajukan menjadi pukul 05.30 WIB.',
                'location_id' => $paroki->getKey(),
                'mass_schedule_id' => null,
                'special_mass_id' => null,
                'starts_at' => Carbon::now((string) config('app.display_timezone'))->subDay()->utc(),
                'ends_at' => Carbon::now((string) config('app.display_timezone'))->addDays(14)->utc(),
                'is_active' => true,
            ],
        );
    }

    /**
     * Phase 05 — a handful of published articles and upcoming activities.
     */
    private function seedNewsAndAgenda(User $author, Media $media, ?string $imagePath): void
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

    /**
     * Phase 06A — the parish profile and its dependent sections.
     */
    private function seedParishProfile(Media $media, ?string $imagePath): void
    {
        ParishProfile::query()->updateOrCreate(
            [],
            [
                'name' => 'Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau',
                'short_description' => 'Komunitas umat Katolik di Putussibau, Kabupaten Kapuas Hulu, Keuskupan Sintang.',
                'description' => '<p>Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau '
                    .'melayani umat di wilayah Putussibau dan sekitarnya. '
                    .'Hidup menggereja diwujudkan melalui peribadatan, katekese, dan karya sosial.</p>',
                'history_description' => '<p>Paroki ini bertumbuh dari komunitas kecil umat menjadi '
                    .'paroki yang melayani beberapa stasi dan lingkungan. Perjalanan tersebut '
                    .'menjadi bagian dari sejarah iman umat di Kapuas Hulu.</p>',
                'address' => 'Putussibau, Kabupaten Kapuas Hulu, Kalimantan Barat',
                'phone' => null,
                'email' => null,
                'website' => null,
                'established_year' => 1956,
                'logo_media_id' => $media->getKey(),
                'logo_alt' => 'Logo Paroki',
                'cover_media_id' => $media->getKey(),
                'cover_alt' => 'Gambar sampul Paroki',
                'map_url' => null,
                'video_url' => null,
                'is_published' => true,
            ],
        );

        ParishServiceArea::query()->updateOrCreate(
            [],
            [
                'content' => '<p>Wilayah pelayanan paroki mencakup umat di sekitar Putussibau '
                    .'beserta stasi dan kapel yang berada di bawahnya. '
                    .'Pembagian wilayah dan lingkungan memudahkan pendampingan umat.</p>',
                'map_media_id' => $media->getKey(),
                'map_url' => null,
                'is_published' => true,
            ],
        );

        /** @var list<array{title: string, year: int, description: string, sort: int}> $history */
        $history = [
            ['title' => 'Paroki mulai dilayani secara tetap', 'year' => 1956, 'description' => '<p>Umat mulai dilayani secara tetap oleh seorang imam.</p>', 'sort' => 1],
            ['title' => 'Pembangunan gereja paroki', 'year' => 1972, 'description' => '<p>Gereja paroki dibangun dan diberkati bersama seluruh umat.</p>', 'sort' => 2],
            ['title' => 'Pembentukan stasi dan lingkungan', 'year' => 1990, 'description' => '<p>Wilayah pelayanan ditata ke dalam stasi dan lingkungan.</p>', 'sort' => 3],
            ['title' => 'Renovasi dan penataan pastoral', 'year' => 2015, 'description' => '<p>Gereja direnovasi dan tata kelola pastoral diperbarui.</p>', 'sort' => 4],
        ];

        foreach ($history as $item) {
            ParishHistoryItem::query()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'year' => $item['year'],
                    'date' => null,
                    'description' => $item['description'],
                    'media_id' => $media->getKey(),
                    'image_alt' => 'Foto perjalanan sejarah paroki',
                    'sort_order' => $item['sort'],
                    'is_published' => true,
                ],
            );
        }

        $this->seedPastors($media);
        $this->seedBoard($media);
    }

    /**
     * Pastors with assignment history. Exactly one assignment in the whole
     * table is `is_current`.
     */
    private function seedPastors(Media $media): void
    {
        $current = Pastor::query()->updateOrCreate(
            ['name' => 'RD. Yohanes Baptista'],
            [
                'title' => 'Pst.',
                'photo_media_id' => $media->getKey(),
                'photo_alt' => 'Foto pastor paroki',
                'bio' => '<p>Melayani sebagai pastor paroki dan mendampingi umat di wilayah Putussibau.</p>',
                'sort_order' => 1,
                'is_published' => true,
            ],
        );

        $previous = Pastor::query()->updateOrCreate(
            ['name' => 'RD. Petrus Kanisius'],
            [
                'title' => 'Pst.',
                'photo_media_id' => $media->getKey(),
                'photo_alt' => 'Foto pastor terdahulu',
                'bio' => '<p>Pernah melayani sebagai pastor paroki pada periode sebelumnya.</p>',
                'sort_order' => 2,
                'is_published' => true,
            ],
        );

        // Clear every current flag first, then set exactly one, so the seeder
        // itself never leaves two pastors in office.
        PastorAssignment::query()->update(['is_current' => false]);

        PastorAssignment::query()->updateOrCreate(
            ['pastor_id' => $previous->getKey(), 'start_year' => 2008],
            [
                'position' => PastorPosition::PastorParoki,
                'end_year' => 2016,
                'is_current' => false,
                'description' => 'Masa penugasan sebagai pastor paroki.',
            ],
        );

        PastorAssignment::query()->updateOrCreate(
            ['pastor_id' => $current->getKey(), 'start_year' => 2016],
            [
                'position' => PastorPosition::PastorParoki,
                'end_year' => null,
                'is_current' => true,
                'description' => 'Sedang bertugas sebagai pastor paroki.',
            ],
        );

        PastorAssignment::query()->updateOrCreate(
            ['pastor_id' => $current->getKey(), 'start_year' => 2010],
            [
                'position' => PastorPosition::PastorRekan,
                'end_year' => 2016,
                'is_current' => false,
                'description' => 'Masa penugasan sebagai pastor rekan.',
            ],
        );
    }

    /**
     * Two leadership periods — one active, one archive — with their members.
     */
    private function seedBoard(Media $media): void
    {
        // One active period only, same reasoning as the pastor assignment.
        ParishBoardPeriod::query()->update(['is_active' => false]);

        $active = ParishBoardPeriod::query()->updateOrCreate(
            ['name' => 'Periode 2023–2026'],
            [
                'start_year' => 2023,
                'end_year' => 2026,
                'description' => 'Periode kepengurusan yang sedang berjalan.',
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 1,
            ],
        );

        $archive = ParishBoardPeriod::query()->updateOrCreate(
            ['name' => 'Periode 2020–2023'],
            [
                'start_year' => 2020,
                'end_year' => 2023,
                'description' => 'Periode kepengurusan sebelumnya.',
                'is_active' => false,
                'is_published' => true,
                'sort_order' => 2,
            ],
        );

        /** @var list<array{0: ParishBoardPeriod, 1: string, 2: string, 3: int}> $members */
        $members = [
            [$active, 'Antonius Sudirman', 'Ketua', 1],
            [$active, 'Maria Fransiska', 'Wakil Ketua', 2],
            [$active, 'Yohanes Hendra', 'Sekretaris', 3],
            [$active, 'Theresia Lestari', 'Bendahara', 4],
            [$archive, 'Paulus Gunawan', 'Ketua', 1],
            [$archive, 'Agnes Ratna', 'Sekretaris', 2],
        ];

        foreach ($members as [$period, $name, $position, $sort]) {
            ParishBoardMember::query()->updateOrCreate(
                ['parish_board_period_id' => $period->getKey(), 'name' => $name],
                [
                    'position' => $position,
                    'photo_media_id' => $media->getKey(),
                    'photo_alt' => 'Foto pengurus paroki',
                    'description' => null,
                    'sort_order' => $sort,
                    'is_published' => true,
                ],
            );
        }
    }
}
