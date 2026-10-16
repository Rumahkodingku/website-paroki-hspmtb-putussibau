<?php

namespace Database\Seeders;

use App\Enums\AreaType;
use App\Enums\CommunityStatus;
use App\Enums\DayOfWeek;
use App\Enums\EventStatus;
use App\Enums\LocationType;
use App\Enums\MediaStatus;
use App\Enums\PastorPosition;
use App\Enums\PostStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Area;
use App\Models\Community;
use App\Models\CommunityCategory;
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
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceFaq;
use App\Models\ServiceRequirement;
use App\Models\ServiceStep;
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
        $this->seedServices($media);
        $this->seedCommunities($media);

        $this->command->info('Data contoh Profil Paroki, Jadwal Misa, Berita & Agenda, Pelayanan, dan Komunitas dimuat.');
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

    /**
     * Phase 06B — the three categories, the six services, and the ordered
     * requirement / step / FAQ lists behind each service page.
     *
     * The text is derived from PRD Lampiran A, which D-10 itself calls sample
     * content. It is sample data, not parish data: it exists so every CTA state
     * and every public section can be looked at. A production install never sees
     * it, because this seeder only runs while `demo.enabled` is true and
     * `composer setup` migrates without `--seed`.
     *
     * The six services are created inactive by ServiceSeeder; this method
     * activates them, so `/pelayanan` is populated for review. The Tipe CTA is
     * spread on purpose — one form open, one closed with a message, one with no
     * form at all, and the emergency service — so all four states render.
     */
    private function seedServices(Media $media): void
    {
        /** @var array<string, array{description: string}> $categories */
        $categories = [
            'inisiasi' => [
                'description' => 'Sakramen inisiasi umat Katolik: Baptis, Komuni Pertama, dan Krisma.',
            ],
            'perkawinan' => [
                'description' => 'Persiapan dan pelaksanaan sakramen pernikahan di Gereja Katolik.',
            ],
            'orang-sakit' => [
                'description' => 'Pelayanan perminyakan orang sakit bagi umat yang sakit berat atau lanjut usia.',
            ],
        ];

        foreach ($categories as $slug => $fields) {
            ServiceCategory::query()->where('slug', $slug)->update($fields);
        }

        $services = $this->serviceSamples();

        foreach ($services as $sample) {
            $categoryId = ServiceCategory::query()->where('slug', $sample['category'])->value('id');

            if ($categoryId === null) {
                continue;
            }

            $service = Service::withTrashed()->updateOrCreate(
                ['slug' => $sample['slug']],
                [
                    'title' => $sample['title'],
                    'service_category_id' => $categoryId,
                    'thumbnail_media_id' => $media->getKey(),
                    'thumbnail_alt' => 'Ilustrasi layanan '.$sample['title'].'.',
                    'summary' => $sample['summary'],
                    'introduction' => $sample['introduction'],
                    'schedule_info' => $sample['schedule_info'],
                    'fee_info' => $sample['fee_info'],
                    'contact_name' => $sample['contact_name'],
                    'contact_phone' => $sample['contact_phone'],
                    'form_url' => $sample['form_url'],
                    'form_button_label' => 'Daftar Sekarang',
                    'is_form_open' => $sample['is_form_open'],
                    'form_closed_message' => $sample['form_closed_message'],
                    'is_emergency' => $sample['is_emergency'],
                    'is_active' => true,
                    'sort_order' => $sample['sort_order'],
                ],
            );

            $this->replaceServiceChildren(
                $service,
                $sample['requirements'],
                $sample['steps'],
                $sample['faqs'],
            );
        }
    }

    /**
     * Sample communities, their categories, and the area hierarchy.
     *
     * This is sample data, not parish data — but every column is filled on
     * purpose, so a preview shows the whole detail page: the area hierarchy and
     * its leaders, the service areas, the vision/mission, the joining
     * information, and both contacts. The values are obvious placeholders
     * ("… Contoh", phone numbers under 62811002…), never a real person or a real
     * parish phone number (D-34/D-53).
     *
     * show_contact is on for some communities and areas and off for others, so
     * the privacy rule (XC-P1) has both states to render in a preview.
     *
     * Keyed on slug with withTrashed + deleted_at reset, so a reseed edits rather
     * than colliding with a soft-deleted row that still holds the slug.
     *
     * @see docs/DECISIONS.md D-34, D-53, D-55
     */
    private function seedCommunities(Media $media): void
    {
        /** @var array<string, array{name: string, sort_order: int}> $categories */
        $categories = [
            'organisasi-kategorial' => ['name' => 'Organisasi/Kategorial', 'sort_order' => 0],
            'kelompok-doa-pelayanan' => ['name' => 'Kelompok Doa dan Pelayanan', 'sort_order' => 1],
            'wilayah-lingkungan' => ['name' => 'Wilayah dan Lingkungan', 'sort_order' => 2],
        ];

        foreach ($categories as $slug => $fields) {
            CommunityCategory::withTrashed()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $fields['name'],
                    'description' => 'Contoh kategori komunitas untuk pratinjau.',
                    'sort_order' => $fields['sort_order'],
                    'is_active' => true,
                    'deleted_at' => null,
                ],
            );
        }

        /*
         * The area hierarchy. Order matters: a lingkungan names its parent by
         * slug, and the parent is created first so the key exists. `parent` is
         * null for the two wilayah and the stasi (PRD COM-02, D-55 point 3).
         */
        /** @var array<string, array{name: string, type: AreaType, parent: string|null, leader: string, phone: string, show_contact: bool, sort: int}> $areaSamples */
        $areaSamples = [
            'wilayah-contoh-utara' => [
                'name' => 'Wilayah Contoh Utara',
                'type' => AreaType::Wilayah,
                'parent' => null,
                'leader' => 'Ketua Wilayah Utara Contoh',
                'phone' => '628110020001',
                'show_contact' => true,
                'sort' => 0,
            ],
            'wilayah-contoh-selatan' => [
                'name' => 'Wilayah Contoh Selatan',
                'type' => AreaType::Wilayah,
                'parent' => null,
                'leader' => 'Ketua Wilayah Selatan Contoh',
                'phone' => '628110020002',
                'show_contact' => false,
                'sort' => 1,
            ],
            'lingkungan-contoh-melati' => [
                'name' => 'Lingkungan Contoh Melati',
                'type' => AreaType::Lingkungan,
                'parent' => 'wilayah-contoh-utara',
                'leader' => 'Ketua Lingkungan Melati Contoh',
                'phone' => '628110020003',
                'show_contact' => true,
                'sort' => 0,
            ],
            'lingkungan-contoh-kenanga' => [
                'name' => 'Lingkungan Contoh Kenanga',
                'type' => AreaType::Lingkungan,
                'parent' => 'wilayah-contoh-utara',
                'leader' => 'Ketua Lingkungan Kenanga Contoh',
                'phone' => '628110020004',
                'show_contact' => true,
                'sort' => 1,
            ],
            'lingkungan-contoh-anggrek' => [
                'name' => 'Lingkungan Contoh Anggrek',
                'type' => AreaType::Lingkungan,
                // Top-level on purpose: this parish has no wilayah, so its centre
                // lingkungan have no parent (D-57).
                'parent' => null,
                'leader' => 'Ketua Lingkungan Anggrek Contoh',
                'phone' => '628110020005',
                'show_contact' => false,
                'sort' => 0,
            ],
            'stasi-contoh-santo-yosef' => [
                'name' => 'Stasi Contoh Santo Yosef',
                'type' => AreaType::Stasi,
                'parent' => null,
                'leader' => 'Ketua Stasi Santo Yosef Contoh',
                'phone' => '628110020006',
                'show_contact' => true,
                'sort' => 2,
            ],
        ];

        /** @var array<string, Area> $areas */
        $areas = [];

        foreach ($areaSamples as $slug => $sample) {
            $parentId = $sample['parent'] === null
                ? null
                : $areas[$sample['parent']]->getKey();

            $areas[$slug] = Area::withTrashed()->updateOrCreate(
                ['slug' => $slug],
                [
                    'parent_id' => $parentId,
                    'name' => $sample['name'],
                    'type' => $sample['type'],
                    'description' => 'Contoh '.$sample['name'].' untuk pratinjau.',
                    'leader_name' => $sample['leader'],
                    'contact_phone' => $sample['phone'],
                    'show_contact' => $sample['show_contact'],
                    'sort_order' => $sample['sort'],
                    'is_active' => true,
                    'deleted_at' => null,
                ],
            );
        }

        /*
         * Six communities, every column filled. All are active and published so
         * the public directory lists them; two carry no contact to keep the
         * privacy rule visible in a preview.
         */
        /** @var list<array{slug: string, name: string, category: string, description: string, vision: string, mission: string, meeting_schedule: string, meeting_location: string, eligibility: string, joining_info: string, contact_name: string, contact_phone: string, show_contact: bool}> $communities */
        $communities = [
            [
                'slug' => 'omk-contoh',
                'name' => 'OMK (Orang Muda Katolik) Contoh',
                'category' => 'organisasi-kategorial',
                'description' => 'Contoh komunitas Orang Muda Katolik yang menghimpun umat muda paroki untuk bertumbuh dalam iman dan pelayanan.',
                'vision' => 'Menjadi generasi muda Katolik yang aktif, kreatif, dan berakar pada iman.',
                'mission' => 'Mengadakan pendampingan iman, kegiatan sosial, dan karya kreatif bagi umat muda; serta mendukung kegiatan paroki.',
                'meeting_schedule' => 'Setiap Minggu pukul 16.00 (contoh).',
                'meeting_location' => 'Aula Paroki (contoh)',
                'eligibility' => 'Umat muda paroki yang telah menerima Krisma dan bersedia aktif dalam kegiatan komunitas.',
                'joining_info' => 'Menghubungi pengurus komunitas atau sekretariat paroki untuk pendaftaran anggota baru.',
                'contact_name' => 'Pengurus OMK Contoh',
                'contact_phone' => '628110020101',
                'show_contact' => true,
            ],
            [
                'slug' => 'wkri-contoh',
                'name' => 'WKRI (Wanita Katolik RI) Contoh',
                'category' => 'organisasi-kategorial',
                'description' => 'Contoh komunitas Wanita Katolik RI yang menghimpun ibu-ibu umat paroki dalam doa dan karya kasih.',
                'vision' => 'Menjadi wanita Katolik yang beriman, mandiri, dan peduli sesama.',
                'mission' => 'Mengembangkan doa bersama, karya sosial, dan pendampingan keluarga; serta mendukung kegiatan paroki.',
                'meeting_schedule' => 'Setiap Sabtu pertama pukul 15.00 (contoh).',
                'meeting_location' => 'Ruang Pertemuan Paroki (contoh)',
                'eligibility' => 'Wanita Katolik paroki yang bersedia aktif dalam kegiatan komunitas.',
                'joining_info' => 'Mendaftar melalui pengurus komunitas atau sekretariat paroki.',
                'contact_name' => 'Pengurus WKRI Contoh',
                'contact_phone' => '628110020102',
                'show_contact' => true,
            ],
            [
                'slug' => 'legio-mariae-contoh',
                'name' => 'Legio Mariae Contoh',
                'category' => 'kelompok-doa-pelayanan',
                'description' => 'Contoh kelompok doa Legio Mariae yang berdoa bersama dan melayani umat, khususnya yang sakit dan berkekurangan.',
                'vision' => 'Meneladan Bunda Maria dalam doa dan pelayanan kepada sesama.',
                'mission' => 'Mengadakan doa rosario bersama, kunjungan kepada umat yang sakit, dan karya belas kasih.',
                'meeting_schedule' => 'Setiap Selasa pukul 18.00 (contoh).',
                'meeting_location' => 'Kapel Paroki (contoh)',
                'eligibility' => 'Umat Katolik paroki yang bersedia berdoa dan melayani sesama.',
                'joining_info' => 'Menghubungi pengurus kelompok atau sekretariat paroki.',
                'contact_name' => 'Pengurus Legio Mariae Contoh',
                'contact_phone' => '628110020103',
                'show_contact' => false,
            ],
            [
                'slug' => 'koor-paroki-contoh',
                'name' => 'Koor Paroki Contoh',
                'category' => 'kelompok-doa-pelayanan',
                'description' => 'Contoh paduan suara paroki yang melayani nyanyian liturgi pada perayaan misa dan kegiatan paroki.',
                'vision' => 'Melayani liturgi dengan nyanyian yang indah dan penuh iman.',
                'mission' => 'Menyiapkan nyanyian misa, latihan rutin, dan mendukung perayaan liturgi paroki.',
                'meeting_schedule' => 'Latihan setiap Sabtu pukul 17.00 (contoh).',
                'meeting_location' => 'Aula Paroki (contoh)',
                'eligibility' => 'Umat paroki yang memiliki minat dan kesediaan berlatih bernyanyi.',
                'joining_info' => 'Mengikuti latihan rutin atau menghubungi pelatih koor melalui sekretariat paroki.',
                'contact_name' => 'Pelatih Koor Contoh',
                'contact_phone' => '628110020104',
                'show_contact' => true,
            ],
            [
                'slug' => 'lingkungan-melati-contoh',
                'name' => 'Lingkungan Contoh Melati',
                'category' => 'wilayah-lingkungan',
                'description' => 'Contoh komunitas lingkungan Melati yang menghimpun umat dalam doa dan kegiatan bersama di tingkat lingkungan.',
                'vision' => 'Menjadi lingkungan yang guyub, beriman, dan saling mendukung.',
                'mission' => 'Mengadakan doa lingkungan rutin, pendampingan umat, dan karya sosial di lingkungan.',
                'meeting_schedule' => 'Doa bersama setiap Rabu pukul 19.00 (contoh).',
                'meeting_location' => 'Rumah umat bergilir (contoh)',
                'eligibility' => 'Umat yang berdomisili di Lingkungan Contoh Melati.',
                'joining_info' => 'Menghubungi ketua lingkungan setempat.',
                'contact_name' => 'Ketua Lingkungan Melati Contoh',
                'contact_phone' => '628110020105',
                'show_contact' => true,
            ],
            [
                'slug' => 'lingkungan-kenanga-contoh',
                'name' => 'Lingkungan Contoh Kenanga',
                'category' => 'wilayah-lingkungan',
                'description' => 'Contoh komunitas lingkungan Kenanga yang menghimpun umat dalam doa dan kegiatan bersama di tingkat lingkungan.',
                'vision' => 'Menjadi lingkungan yang guyub, beriman, dan saling mendukung.',
                'mission' => 'Mengadakan doa lingkungan rutin, pendampingan umat, dan karya sosial di lingkungan.',
                'meeting_schedule' => 'Doa bersama setiap Selasa pukul 19.00 (contoh).',
                'meeting_location' => 'Rumah umat bergilir (contoh)',
                'eligibility' => 'Umat yang berdomisili di Lingkungan Contoh Kenanga.',
                'joining_info' => 'Menghubungi ketua lingkungan setempat.',
                'contact_name' => 'Ketua Lingkungan Kenanga Contoh',
                'contact_phone' => '628110020106',
                'show_contact' => false,
            ],
        ];

        foreach ($communities as $index => $sample) {
            $categoryId = CommunityCategory::query()->where('slug', $sample['category'])->value('id');

            if ($categoryId === null) {
                continue;
            }

            $community = Community::withTrashed()->updateOrCreate(
                ['slug' => $sample['slug']],
                [
                    'community_category_id' => $categoryId,
                    'name' => $sample['name'],
                    'logo_media_id' => $media->getKey(),
                    'logo_alt' => 'Logo '.$sample['name'].'.',
                    'thumbnail_media_id' => $media->getKey(),
                    'thumbnail_alt' => 'Ilustrasi '.$sample['name'].'.',
                    'description' => $sample['description'],
                    'vision' => $sample['vision'],
                    'mission' => $sample['mission'],
                    'meeting_schedule' => $sample['meeting_schedule'],
                    'meeting_location' => $sample['meeting_location'],
                    'eligibility' => $sample['eligibility'],
                    'joining_info' => $sample['joining_info'],
                    'contact_name' => $sample['contact_name'],
                    'contact_phone' => $sample['contact_phone'],
                    'show_contact' => $sample['show_contact'],
                    'status' => CommunityStatus::Active,
                    'is_published' => true,
                    'published_at' => now()->subDays($index + 1),
                    'deleted_at' => null,
                ],
            );
        }
    }

    /**
     * The six sample services.
     *
     * @return list<array{
     *     slug: string,
     *     title: string,
     *     category: string,
     *     summary: string,
     *     introduction: string,
     *     schedule_info: string,
     *     fee_info: string|null,
     *     contact_name: string,
     *     contact_phone: string,
     *     form_url: string|null,
     *     is_form_open: bool,
     *     form_closed_message: string|null,
     *     is_emergency: bool,
     *     sort_order: int,
     *     requirements: list<array{text: string, note?: string|null}>,
     *     steps: list<array{title: string, description?: string|null}>,
     *     faqs: list<array{question: string, answer: string}>
     * }>
     */
    private function serviceSamples(): array
    {
        return [
            // Baptis Bayi — form open.
            [
                'slug' => 'baptis-bayi',
                'title' => 'Baptis Bayi',
                'category' => 'inisiasi',
                'summary' => 'Sakramen pembaptisan bagi bayi dan anak kecil dalam keluarga Katolik.',
                'introduction' => '<p>Baptis Bayi dilayani bagi bayi dan anak kecil dari keluarga Katolik. '
                    .'Orang tua dan wali baptis mempersiapkan diri melalui pembekalan yang diselenggarakan paroki.</p>',
                'schedule_info' => 'Pelaksanaan disepakati bersama pastor/sekretariat. Pembekalan orang tua dan '
                    .'wali baptis mengikuti jadwal katekese paroki.',
                'fee_info' => null,
                'contact_name' => 'Sekretariat Paroki',
                'contact_phone' => '628110000001',
                'form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSfCONTOHbaptisBayi/viewform',
                'is_form_open' => true,
                'form_closed_message' => null,
                'is_emergency' => false,
                'sort_order' => 1,
                'requirements' => [
                    ['text' => 'Surat nikah gereja orang tua, atau keterangan khusus bila belum menikah gereja'],
                    ['text' => 'Akta kelahiran atau surat keterangan lahir bayi'],
                    ['text' => 'Fotokopi Kartu Keluarga'],
                    ['text' => 'Surat keterangan dari ketua lingkungan/wilayah'],
                    ['text' => 'Data wali baptis beserta surat baptis/krisma wali', 'note' => 'Wali baptis beragama Katolik dan sudah menerima Komuni dan Krisma.'],
                    ['text' => 'Pas foto sesuai ketentuan sekretariat', 'note' => 'Bila diminta.'],
                ],
                'steps' => [
                    ['title' => 'Membaca syarat dan menyiapkan berkas', 'description' => 'Orang tua membaca syarat, mengisi pendaftaran, dan menyiapkan dokumen.'],
                    ['title' => 'Mengikuti pembekalan orang tua dan wali baptis'],
                    ['title' => 'Menentukan jadwal pelaksanaan bersama pastor/sekretariat'],
                    ['title' => 'Pelaksanaan sakramen baptis'],
                    ['title' => 'Pengambilan surat baptis', 'description' => 'Setelah pencatatan di buku baptis paroki.'],
                ],
                'faqs' => [
                    ['question' => 'Apakah orang tua wajib menikah secara gereja?', 'answer' => 'Pada umumnya ya. Bila belum, silakan menghubungi sekretariat untuk membicarakan langkah yang perlu disiapkan.'],
                    ['question' => 'Siapa saja yang boleh menjadi wali baptis?', 'answer' => 'Wali baptis adalah umat Katolik yang telah menerima Komuni dan Krisma serta bersedia mendampingi pertumbuhan iman anak.'],
                    ['question' => 'Apakah ada biaya?', 'answer' => 'Persembahan bersifat sukarela. Silakan menghubungi sekretariat untuk informasi lebih lanjut.'],
                    ['question' => 'Berapa lama prosesnya?', 'answer' => 'Waktu bergantung pada jadwal pembekalan dan kesepakatan pelaksanaan bersama pastor/sekretariat.'],
                ],
            ],

            // Baptis Dewasa — contact only, no form.
            [
                'slug' => 'baptis-dewasa',
                'title' => 'Baptis Dewasa',
                'category' => 'inisiasi',
                'summary' => 'Pendampingan bagi calon baptis dewasa yang ingin menjadi Katolik.',
                'introduction' => '<p>Baptis Dewasa diperuntukkan bagi orang dewasa yang ingin menerima sakramen '
                    .'inisiasi. Calon didampingi melalui masa katekumenat dan persiapan yang sesuai.</p>',
                'schedule_info' => 'Mengikuti masa katekumenat dan jadwal pendampingan paroki.',
                'fee_info' => null,
                'contact_name' => 'Sekretariat Paroki',
                'contact_phone' => '628110000002',
                'form_url' => null,
                'is_form_open' => false,
                'form_closed_message' => null,
                'is_emergency' => false,
                'sort_order' => 2,
                'requirements' => [
                    ['text' => 'Fotokopi KTP dan Kartu Keluarga'],
                    ['text' => 'Surat permohonan menjadi Katolik'],
                    ['text' => 'Surat keterangan pernah/belum dibaptis', 'note' => 'Bila diperlukan.'],
                    ['text' => 'Data wali baptis'],
                    ['text' => 'Surat keterangan/izin dari pihak terkait', 'note' => 'Sesuai ketentuan paroki, misalnya bila ada pernikahan.'],
                ],
                'steps' => [
                    ['title' => 'Menyampaikan permohonan kepada pastor paroki/sekretariat'],
                    ['title' => 'Mengikuti masa katekumenat', 'description' => 'Hingga dinyatakan siap.'],
                    ['title' => 'Mengikuti ritus penerimaan dan tahapan katekumenat'],
                    ['title' => 'Menerima sakramen inisiasi', 'description' => 'Baptis, Krisma, dan Ekaristi pada waktu yang ditetapkan.'],
                    ['title' => 'Pencatatan dan penerimaan surat baptis'],
                ],
                'faqs' => [
                    ['question' => 'Apakah harus mengikuti katekumenat?', 'answer' => 'Ya. Masa katekumenat adalah bagian dari persiapan dan pendampingan sebelum menerima sakramen inisiasi.'],
                    ['question' => 'Berapa lama masa persiapannya?', 'answer' => 'Lamanya mengikuti jadwal pendampingan paroki dan kesiapan calon.'],
                    ['question' => 'Apakah perlu wali baptis?', 'answer' => 'Ya, calon perlu menyiapkan data wali baptis sesuai ketentuan paroki.'],
                ],
            ],

            // Komuni Pertama — form closed.
            [
                'slug' => 'komuni-pertama',
                'title' => 'Komuni Pertama',
                'category' => 'inisiasi',
                'summary' => 'Persiapan anak-anak untuk menerima Komuni Pertama.',
                'introduction' => '<p>Persiapan Komuni Pertama membantu anak memahami dan menyambut Ekaristi '
                    .'untuk pertama kalinya, didampingi orang tua dan pembina katekese.</p>',
                'schedule_info' => 'Mengikuti jadwal pelajaran persiapan, termasuk pengakuan dosa pertama, dan gladi.',
                'fee_info' => null,
                'contact_name' => 'Sekretariat Paroki',
                'contact_phone' => '628110000003',
                'form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSfCONTOHkomuniPertama/viewform',
                'is_form_open' => false,
                'form_closed_message' => 'Pendaftaran Komuni Pertama untuk periode ini sudah ditutup. Hubungi sekretariat untuk informasi periode berikutnya.',
                'is_emergency' => false,
                'sort_order' => 3,
                'requirements' => [
                    ['text' => 'Fotokopi surat baptis'],
                    ['text' => 'Fotokopi Kartu Keluarga'],
                    ['text' => 'Surat keterangan telah mengikuti katekese/persiapan Komuni Pertama'],
                    ['text' => 'Persetujuan orang tua/wali'],
                ],
                'steps' => [
                    ['title' => 'Pendaftaran dan tindak lanjut oleh sekretariat/pembina katekese'],
                    ['title' => 'Mengikuti pelajaran persiapan', 'description' => 'Termasuk pengakuan dosa pertama sesuai jadwal.'],
                    ['title' => 'Gladi/latihan bersama'],
                    ['title' => 'Perayaan Komuni Pertama dalam misa yang ditentukan'],
                ],
                'faqs' => [
                    ['question' => 'Untuk usia berapa Komuni Pertama disiapkan?', 'answer' => 'Umumnya bagi anak yang telah siap mengikuti katekese persiapan. Silakan menghubungi sekretariat untuk ketentuannya.'],
                    ['question' => 'Apakah pendaftaran selalu dibuka?', 'answer' => 'Pendaftaran dibuka pada periode tertentu. Bila ditutup, pengumuman akan tampil pada halaman ini.'],
                    ['question' => 'Apakah orang tua ikut mendampingi?', 'answer' => 'Ya, orang tua/wali mendampingi anak selama masa persiapan.'],
                ],
            ],

            // Krisma — form open.
            [
                'slug' => 'krisma',
                'title' => 'Krisma',
                'category' => 'inisiasi',
                'summary' => 'Persiapan penerimaan sakramen Krisma bagi umat yang telah dibaptis.',
                'introduction' => '<p>Sakramen Krisma merupakan bagian dari inisiasi Katolik. Peserta dipersiapkan '
                    .'melalui katekese dan pendampingan sebelum perayaan yang dipimpin Uskup atau pastor yang diberi mandat.</p>',
                'schedule_info' => 'Mengikuti katekese persiapan Krisma sesuai jadwal paroki.',
                'fee_info' => null,
                'contact_name' => 'Sekretariat Paroki',
                'contact_phone' => '628110000004',
                'form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSfCONTOHkrisma/viewform',
                'is_form_open' => true,
                'form_closed_message' => null,
                'is_emergency' => false,
                'sort_order' => 4,
                'requirements' => [
                    ['text' => 'Fotokopi surat baptis dan bukti Komuni Pertama'],
                    ['text' => 'Fotokopi Kartu Keluarga'],
                    ['text' => 'Surat keterangan telah mengikuti katekese persiapan Krisma'],
                    ['text' => 'Data wali krisma beserta surat baptis/krisma wali'],
                    ['text' => 'Surat pengantar dari lingkungan', 'note' => 'Bila diminta.'],
                ],
                'steps' => [
                    ['title' => 'Mengisi pendaftaran peserta'],
                    ['title' => 'Mengikuti katekese persiapan Krisma sesuai jadwal'],
                    ['title' => 'Pengakuan dosa dan persiapan akhir'],
                    ['title' => 'Perayaan sakramen Krisma'],
                ],
                'faqs' => [
                    ['question' => 'Siapa yang dapat menerima Krisma?', 'answer' => 'Umat yang telah dibaptis dan mempersiapkan diri sesuai ketentuan paroki.'],
                    ['question' => 'Apakah perlu wali krisma?', 'answer' => 'Ya, peserta menyiapkan data wali krisma sesuai ketentuan.'],
                    ['question' => 'Kapan perayaan Krisma diadakan?', 'answer' => 'Perayaan mengikuti jadwal Uskup atau pastor yang diberi mandat. Informasi akan disampaikan saat persiapan.'],
                ],
            ],

            // Pernikahan — form open, with a fee line.
            [
                'slug' => 'pernikahan',
                'title' => 'Pernikahan',
                'category' => 'perkawinan',
                'summary' => 'Persiapan dan pelaksanaan sakramen pernikahan di Gereja Katolik.',
                'introduction' => '<p>Pasangan yang hendak menikah secara Katolik didampingi sejak jauh hari, mulai '
                    .'dari pemeriksaan kanonik, kursus persiapan perkawinan, hingga pelaksanaan pemberkatan.</p>',
                'schedule_info' => 'Hubungi pastor paroki/sekretariat paling lambat beberapa bulan sebelum rencana pernikahan.',
                'fee_info' => 'Biaya administrasi dan persembahan mengikuti ketentuan paroki. Silakan menghubungi sekretariat untuk rinciannya.',
                'contact_name' => 'Sekretariat Paroki',
                'contact_phone' => '628110000005',
                'form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSfCONTOHpernikahan/viewform',
                'is_form_open' => true,
                'form_closed_message' => null,
                'is_emergency' => false,
                'sort_order' => 5,
                'requirements' => [
                    ['text' => 'Surat baptis terbaru kedua calon'],
                    ['text' => 'Surat keterangan belum menikah/status', 'note' => 'Dari lingkungan atau instansi terkait.'],
                    ['text' => 'Fotokopi KTP dan Kartu Keluarga kedua calon'],
                    ['text' => 'Pas foto berdampingan'],
                    ['text' => 'Surat keterangan telah mengikuti kursus persiapan perkawinan'],
                    ['text' => 'Surat izin/dispensasi bila ada halangan', 'note' => 'Sesuai ketentuan Gereja.'],
                    ['text' => 'Dokumen pendukung administrasi sipil', 'note' => 'Sesuai ketentuan pemerintah.'],
                ],
                'steps' => [
                    ['title' => 'Menghubungi pastor paroki/sekretariat'],
                    ['title' => 'Pemeriksaan kanonik dan pengisian berkas'],
                    ['title' => 'Mengikuti kursus persiapan perkawinan'],
                    ['title' => 'Pengumuman rencana perkawinan', 'description' => 'Sesuai ketentuan paroki.'],
                    ['title' => 'Pelaksanaan pemberkatan nikah dan pencatatan'],
                    ['title' => 'Pengurusan administrasi sipil'],
                ],
                'faqs' => [
                    ['question' => 'Berapa lama sebelumnya harus mendaftar?', 'answer' => 'Idealnya beberapa bulan sebelum rencana pernikahan, agar seluruh persiapan dapat diselesaikan.'],
                    ['question' => 'Apakah kedua calon harus Katolik?', 'answer' => 'Bila ada perbedaan agama atau gereja, silakan menghubungi pastor paroki untuk ketentuan yang berlaku.'],
                    ['question' => 'Apakah wajib mengikuti kursus persiapan perkawinan?', 'answer' => 'Ya, kursus persiapan perkawinan merupakan bagian dari persiapan pernikahan Katolik.'],
                    ['question' => 'Apakah biaya sudah pasti?', 'answer' => 'Informasi biaya dan persembahan disampaikan oleh sekretariat. Silakan menghubungi sekretariat untuk rinciannya.'],
                ],
            ],

            // Perminyakan Orang Sakit — emergency, direct contact.
            [
                'slug' => 'perminyakan-orang-sakit',
                'title' => 'Pelayanan Perminyakan Orang Sakit',
                'category' => 'orang-sakit',
                'summary' => 'Pelayanan perminyakan orang sakit bagi umat yang sakit berat atau lanjut usia.',
                'introduction' => '<p>Pelayanan perminyakan diberikan kepada umat Katolik yang sakit berat atau lanjut '
                    .'usia. Keluarga atau ketua lingkungan dapat menghubungi nomor pelayanan darurat paroki.</p>',
                'schedule_info' => 'Dilayani sesuai kebutuhan dan kondisi orang sakit, setelah keluarga menghubungi nomor pelayanan darurat.',
                'fee_info' => null,
                'contact_name' => 'Petugas Pelayanan Orang Sakit',
                'contact_phone' => '628110000006',
                'form_url' => null,
                'is_form_open' => false,
                'form_closed_message' => null,
                'is_emergency' => true,
                'sort_order' => 6,
                'requirements' => [
                    ['text' => 'Tidak ada syarat dokumen', 'note' => 'Informasi dasar yang dibutuhkan: nama, usia, alamat/lokasi (rumah atau rumah sakit), kondisi singkat, dan nomor yang dapat dihubungi.'],
                ],
                'steps' => [
                    ['title' => 'Menghubungi nomor pelayanan darurat paroki', 'description' => 'Melalui telepon atau WhatsApp.'],
                    ['title' => 'Menyampaikan data dasar dan lokasi orang sakit'],
                    ['title' => 'Pastor atau petugas menyesuaikan kunjungan'],
                    ['title' => 'Keluarga menyiapkan suasana doa sederhana', 'description' => 'Bila memungkinkan.'],
                ],
                'faqs' => [
                    ['question' => 'Siapa yang dapat menghubungi?', 'answer' => 'Keluarga atau ketua lingkungan dapat langsung menghubungi nomor pelayanan darurat paroki.'],
                    ['question' => 'Apakah harus melalui pendaftaran online?', 'answer' => 'Tidak. Layanan ini menggunakan kontak langsung, bukan pendaftaran melalui Google Form.'],
                    ['question' => 'Kapan layanan dapat dilakukan?', 'answer' => 'Petugas akan menyesuaikan waktu kunjungan dengan kondisi orang sakit dan kesiapan keluarga.'],
                ],
            ],
        ];
    }

    /**
     * Replace a service's three ordered child lists.
     *
     * Delete-then-insert rather than updateOrCreate, because the child tables
     * have no unique key to match on and a demo reseed should leave the same
     * number of rows every time. This runs only while `demo.enabled` is true.
     *
     * @param  list<array{text: string, note?: string|null}>  $requirements
     * @param  list<array{title: string, description?: string|null}>  $steps
     * @param  list<array{question: string, answer: string}>  $faqs
     */
    private function replaceServiceChildren(
        Service $service,
        array $requirements,
        array $steps,
        array $faqs,
    ): void {
        ServiceRequirement::query()->where('service_id', $service->getKey())->delete();
        ServiceStep::query()->where('service_id', $service->getKey())->delete();
        ServiceFaq::query()->where('service_id', $service->getKey())->delete();

        foreach ($requirements as $index => $requirement) {
            ServiceRequirement::query()->create([
                'service_id' => $service->getKey(),
                'text' => $requirement['text'],
                'note' => $requirement['note'] ?? null,
                'sort_order' => $index,
            ]);
        }

        foreach ($steps as $index => $step) {
            ServiceStep::query()->create([
                'service_id' => $service->getKey(),
                'title' => $step['title'],
                'description' => $step['description'] ?? null,
                'sort_order' => $index,
            ]);
        }

        foreach ($faqs as $index => $faq) {
            ServiceFaq::query()->create([
                'service_id' => $service->getKey(),
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'sort_order' => $index,
            ]);
        }
    }
}
