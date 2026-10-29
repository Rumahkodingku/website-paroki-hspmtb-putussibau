<?php

namespace Database\Seeders;

use App\Enums\AreaType;
use App\Enums\CommunityStatus;
use App\Models\Area;
use App\Models\Community;
use App\Models\CommunityCategory;
use App\Models\Media;
use Illuminate\Database\Seeder;

class DemoCommunitiesSeeder extends Seeder
{
    public function run(Media $media): void
    {
        // Kategori komunitas kini di-seed oleh CommunityCategorySeeder (master
        // data, selalu jalan). Dipanggil di sini agar seeder ini tetap bisa
        // dijalankan standalone (`php artisan db:seed --class=...`).
        $this->call(CommunityCategorySeeder::class);

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

            Community::withTrashed()->updateOrCreate(
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
}
