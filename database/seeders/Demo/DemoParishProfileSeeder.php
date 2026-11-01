<?php

namespace Database\Seeders\Demo;

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Models\ParishProfile;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

// Seeder untuk profil paroki
class DemoParishProfileSeeder extends Seeder
{
    private const IMAGE = 'illustrations/demo/thumbnail-16-9.png';

    public function run(): void
    {
        $media = $this->dummyMedia();

        ParishProfile::query()->updateOrCreate(
            [],
            [
                'description' => app(HtmlSanitizer::class)->clean($this->descriptionHtml()),
                'established_year' => 1939,
                'cover_media_id' => $media->getKey(),
                'cover_alt' => 'Foto gereja katolik HSPMTB Putussibau',
                'video_title' => 'EXPO PANGGILAN | IMAM SMM',
                'video_description' => 'Dokumentasi kegiatan Expo Panggilan Imam Serikat Maria Montfortan (SMM).',
                'video_url' => 'https://youtu.be/WVRpiOBI3l8?si=u8cJDjb9M18T-ryO',
            ],
        );
    }

    private function dummyMedia(): Media
    {
        $source = public_path(self::IMAGE);

        if (! File::exists($source)) {
            throw new \RuntimeException('Berkas '.self::IMAGE.' tidak ditemukan di folder public/.');
        }

        $disk = (string) config('media.original_disk');
        $path = 'media/originals/dummy-paroki.webp';

        Storage::disk($disk)->makeDirectory(dirname($path));
        Storage::disk($disk)->put($path, (string) File::get($source));

        $media = Media::query()->updateOrCreate(
            ['path' => $path],
            [
                'disk' => $disk,
                'original_name' => 'hero-section.webp',
                'mime_type' => 'image/webp',
                'file_size' => File::size($source),
                'width' => null,
                'height' => null,
                'alt_text' => 'Foto gereja katolik HSPMTB Putussibau',
                'status' => MediaStatus::Pending,
                'uploader_id' => null,
                'error_message' => null,
            ],
        );

        GenerateImageVariants::dispatchSync($media->getKey());

        return $media->fresh()->load('variants');
    }

    private function descriptionHtml(): string
    {
        return '<h2>Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau</h2>'
            .'<p>Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau adalah komunitas umat Katolik yang berada di Kota Putussibau, Kabupaten Kapuas Hulu, Provinsi Kalimantan Barat. Paroki ini merupakan bagian dari Keuskupan Sintang dan mendapatkan pelayanan pastoral dari para imam Serikat Maria Montfortan (SMM).</p>'
            .'<p>Sebagai komunitas Gereja Katolik, Paroki HSPMTB Putussibau menjadi tempat bagi umat untuk bertumbuh dalam iman, merayakan sakramen, mempererat persaudaraan, serta mewujudkan kasih Kristus melalui kehidupan sehari-hari. Paroki ini juga menjadi pusat pelayanan rohani dan kehidupan menggereja bagi umat Katolik di wilayah Putussibau dan sekitarnya.</p>'
            .'<h3>Sejarah Singkat</h3>'
            .'<p>Kehadiran pelayanan misi Katolik di Putussibau telah berlangsung sejak sebelum paroki ini resmi berdiri. Berdasarkan sumber sejarah yang tersedia, Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau didirikan pada <strong>10 Juni 1939</strong>. Sejak awal perkembangannya, pelayanan Gereja turut melibatkan para misionaris dan berbagai pihak yang berkontribusi dalam kehidupan masyarakat setempat.</p>'
            .'<p>Dalam perjalanan sejarahnya, paroki ini terus berkembang sebagai pusat kehidupan iman, pelayanan pastoral, dan kegiatan sosial kemasyarakatan. Salah satu perkembangan penting terjadi pada tahun 1979, ketika wilayah Siut-Melapi dimekarkan menjadi Paroki Penampakan Tuhan (Epifania) Siut-Melapi.</p>'
            .'<p>Hingga kini, Paroki HSPMTB Putussibau terus menjalankan perutusannya untuk melayani umat, membangun persaudaraan, dan menghadirkan nilai-nilai Injil di tengah masyarakat.</p>'
            .'<h3>Kehidupan Menggereja dan Pelayanan</h3>'
            .'<p>Kehidupan menggereja di Paroki HSPMTB Putussibau diwujudkan melalui perayaan Ekaristi, pelayanan sakramen, pembinaan iman, pendampingan pastoral, serta berbagai kegiatan yang melibatkan umat dari beragam kelompok dan generasi.</p>'
            .'<p>Selain pelayanan liturgi, paroki juga menjadi tempat bagi umat untuk mengembangkan kehidupan komunitas, memperkuat solidaritas, dan berpartisipasi dalam kegiatan sosial. Keterlibatan umat dalam berbagai bentuk pelayanan menjadi bagian penting dalam membangun Gereja yang hidup, terbuka, dan dekat dengan masyarakat.</p>'
            .'<h3>Wilayah Pelayanan</h3>'
            .'<p>Pelayanan Paroki HSPMTB Putussibau mencakup komunitas umat di Putussibau dan wilayah sekitarnya. Dalam perkembangannya, pelayanan paroki melibatkan sejumlah stasi yang menjadi bagian dari kehidupan menggereja di wilayah tersebut.</p>'
            .'<p>Keberadaan komunitas-komunitas ini membantu mendekatkan pelayanan pastoral kepada umat, mendukung pembinaan iman, serta mempererat hubungan persaudaraan antarumat. Informasi mengenai daftar stasi dan lingkungan yang berlaku saat ini dapat dilengkapi berdasarkan data resmi terbaru dari pihak paroki.</p>'
            .'<h3>Semangat dan Perutusan</h3>'
            .'<p>Sebagai bagian dari Gereja Katolik, Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau dipanggil untuk terus bertumbuh dalam iman, harapan, dan kasih. Semangat pelayanan diwujudkan melalui partisipasi aktif umat dalam kehidupan menggereja, kepedulian terhadap sesama, serta keterlibatan dalam membangun kehidupan masyarakat yang rukun dan harmonis.</p>'
            .'<p>Dengan mengandalkan penyertaan Tuhan dan meneladani Bunda Maria, paroki diharapkan terus menjadi komunitas iman yang menghadirkan kasih Kristus, memperkuat persaudaraan, dan memberikan pelayanan bagi seluruh umat serta masyarakat di sekitarnya.</p>';
    }
}
