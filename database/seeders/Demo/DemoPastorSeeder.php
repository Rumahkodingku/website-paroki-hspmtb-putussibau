<?php

namespace Database\Seeders\Demo;

use App\Enums\MediaStatus;
use App\Enums\PastorPosition;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds the Pastor module with data compiled from public sources.
 *
 * Demo-only: called by DemoContentSeeder when config('demo.enabled') is true.
 * Pastor identity and assignment history are stored separately (D-52).
 * Years that are not verified are left null.
 */
class DemoPastorSeeder extends Seeder
{
    private const IMAGE = 'illustrations/demo/pastor-paroki.png';

    public function run(): void
    {
        $media = $this->dummyMedia();

        /** @var list<array{name: string, title: string, bio: string, alt: string, sort: int, position: PastorPosition, assignment: string}> $pastors */
        $pastors = $this->pastors();

        foreach ($pastors as $item) {
            $pastor = Pastor::query()->updateOrCreate(
                ['name' => $item['name']],
                [
                    'title' => $item['title'],
                    'photo_media_id' => $media->getKey(),
                    'photo_alt' => $item['alt'],
                    'bio' => app(HtmlSanitizer::class)->clean($item['bio']),
                    'sort_order' => $item['sort'],
                    'is_published' => true,
                ],
            );

            PastorAssignment::query()->updateOrCreate(
                ['pastor_id' => $pastor->getKey(), 'position' => $item['position']],
                [
                    'start_year' => null,
                    'end_year' => null,
                    'is_current' => false,
                    'description' => app(HtmlSanitizer::class)->clean($item['assignment']),
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
        $path = 'media/originals/dummy-pastor.png';

        Storage::disk($disk)->makeDirectory(dirname($path));
        Storage::disk($disk)->put($path, (string) File::get($source));

        $media = Media::query()->updateOrCreate(
            ['path' => $path],
            [
                'disk' => $disk,
                'original_name' => 'pastor-paroki.png',
                'mime_type' => 'image/png',
                'file_size' => File::size($source),
                'width' => null,
                'height' => null,
                'alt_text' => 'Foto pastor paroki',
                'status' => MediaStatus::Pending,
                'uploader_id' => null,
                'error_message' => null,
            ],
        );

        GenerateImageVariants::dispatchSync($media->getKey());

        return $media->fresh()->load('variants');
    }

    /**
     * @return list<array{name: string, title: string, bio: string, alt: string, sort: int, position: PastorPosition, assignment: string}>
     */
    private function pastors(): array
    {
        return [
            [
                'name' => 'Melkior Jelalu',
                'title' => 'RP. Melkior Jelalu, SMM',
                'bio' => '<p>RP. Melkior Jelalu, SMM, tercatat sebagai Pastor Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau dalam dokumen kegiatan paroki pada September 2025.</p><p>Sebagai imam dari Serikat Maria Montfortan (SMM), beliau berkarya dalam pelayanan Gereja Katolik dan kehidupan umat di lingkungan Paroki HSPMTB Putussibau, Keuskupan Sintang.</p><p>Informasi mengenai tahun mulai penugasan, riwayat pendidikan, tahbisan imamat, dan penugasan sebelumnya perlu dilengkapi berdasarkan data resmi dari paroki atau Serikat Maria Montfortan.</p>',
                'alt' => 'RP. Melkior Jelalu, SMM, Pastor Paroki HSPMTB Putussibau',
                'sort' => 1,
                'position' => PastorPosition::PastorParoki,
                'assignment' => 'Tercatat sebagai Pastor Paroki HSPMTB Putussibau dalam dokumen kegiatan paroki tanggal 18 September 2025. Tahun awal penugasan belum terverifikasi.',
            ],
            [
                'name' => 'Stefanus Seli',
                'title' => 'RP. Stefanus Seli, SMM',
                'bio' => '<p>RP. Stefanus Seli, SMM, tercatat sebagai Pastor Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau dalam dokumen kegiatan perayaan HUT ke-86 paroki pada Juni 2025.</p><p>Informasi mengenai masa penugasan beliau di HSPMTB Putussibau, riwayat pelayanan sebelumnya, dan penugasan setelah periode tersebut perlu dikonfirmasi kepada pihak paroki atau Serikat Maria Montfortan.</p>',
                'alt' => 'RP. Stefanus Seli, SMM, dalam pelayanan Gereja Katolik',
                'sort' => 2,
                'position' => PastorPosition::PastorParoki,
                'assignment' => 'Tercatat sebagai Pastor Paroki dalam dokumen kegiatan HUT ke-86 Paroki HSPMTB Putussibau, Juni 2025.',
            ],
            [
                'name' => 'Yakobus Rua Bai',
                'title' => 'RP. Yakobus Rua Bai, SMM',
                'bio' => '<p>RP. Yakobus Rua Bai, SMM, pernah tercatat sebagai Pastor Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau dalam pemberitaan Pemerintah Kabupaten Kapuas Hulu pada Januari 2020.</p><p>Dalam sumber komunitas Serikat Maria Montfortan, beliau juga tercatat sebagai pemimpin dan ekonom komunitas SMM di Putussibau. Informasi mengenai periode lengkap penugasan beliau sebagai Pastor Paroki perlu dikonfirmasi melalui arsip resmi paroki.</p>',
                'alt' => 'RP. Yakobus Rua Bai, SMM',
                'sort' => 3,
                'position' => PastorPosition::PastorParoki,
                'assignment' => 'Tercatat sebagai Pastor Paroki dalam pemberitaan Pemerintah Kabupaten Kapuas Hulu, Januari 2020.',
            ],
            [
                'name' => 'Martinus Tamur',
                'title' => 'RP. Martinus Tamur, SMM',
                'bio' => '<p>RP. Martinus Tamur, SMM, pernah melayani sebagai Pastor Rekan di Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau. Pemberitaan Pemerintah Kabupaten Kapuas Hulu pada Januari 2020 mencatat pergantian tugas Pastor Rekan dari beliau kepada RP. Blasius Lion, SMM.</p><p>Tahun awal penugasan dan riwayat pelayanan lainnya perlu dilengkapi berdasarkan arsip resmi paroki atau Serikat Maria Montfortan.</p>',
                'alt' => 'RP. Martinus Tamur, SMM',
                'sort' => 4,
                'position' => PastorPosition::PastorRekan,
                'assignment' => 'Pastor Rekan sebelum pergantian yang diberitakan pada Januari 2020.',
            ],
            [
                'name' => 'Blasius Lion',
                'title' => 'RP. Blasius Lion, SMM',
                'bio' => '<p>RP. Blasius Lion, SMM, tercatat sebagai Pastor Rekan Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau dalam pemberitaan Pemerintah Kabupaten Kapuas Hulu pada Januari 2020, saat beliau menggantikan RP. Martinus Tamur, SMM.</p><p>Periode lengkap penugasan beliau di HSPMTB Putussibau dan riwayat pelayanan lainnya perlu dikonfirmasi melalui sumber resmi paroki atau Serikat Maria Montfortan.</p>',
                'alt' => 'RP. Blasius Lion, SMM',
                'sort' => 5,
                'position' => PastorPosition::PastorRekan,
                'assignment' => 'Menggantikan RP. Martinus Tamur, SMM sebagai Pastor Rekan berdasarkan pemberitaan Januari 2020.',
            ],
            [
                'name' => 'Bronislawa Saryono Nasredin',
                'title' => 'RP. Bronislawa Saryono Nasredin, SMM',
                'bio' => '<p>RP. Bronislawa Saryono Nasredin, SMM, tercatat sebagai anggota komunitas Serikat Maria Montfortan di Putussibau. Sumber terdahulu juga mengaitkan beliau dengan pelayanan pastoral di Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau.</p><p>Jabatan, periode penugasan, dan riwayat pelayanan beliau perlu dikonfirmasi melalui pihak paroki atau Serikat Maria Montfortan sebelum dipublikasikan sebagai data resmi.</p>',
                'alt' => 'RP. Bronislawa Saryono Nasredin, SMM',
                'sort' => 6,
                'position' => PastorPosition::Lainnya,
                'assignment' => 'Tercatat sebagai anggota komunitas SMM Putussibau; periode dan jabatan pastoral di HSPMTB perlu dikonfirmasi.',
            ],
        ];
    }
}
