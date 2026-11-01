<?php

namespace Database\Seeders\Demo;

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Models\ParishServiceArea;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

// Seeder untuk wilayah pelayanan
class DemoParishServiceAreaSeeder extends Seeder
{
    private const IMAGE = 'illustrations/demo/maps.png';

    public function run(): void
    {
        $media = $this->dummyMedia();

        ParishServiceArea::query()->updateOrCreate(
            [],
            [
                'content' => app(HtmlSanitizer::class)->clean($this->contentHtml()),
                'map_media_id' => $media->getKey(),
                'is_published' => true,
            ],
        );
    }

    /**
     * Copy the demo map into the private disk and process it through the
     * media pipeline, so the public page can render a real image.
     */
    private function dummyMedia(): Media
    {
        $source = public_path(self::IMAGE);

        if (! File::exists($source)) {
            throw new \RuntimeException('Berkas '.self::IMAGE.' tidak ditemukan di folder public/.');
        }

        $disk = (string) config('media.original_disk');
        $path = 'media/originals/dummy-wilayah.png';

        Storage::disk($disk)->makeDirectory(dirname($path));
        Storage::disk($disk)->put($path, (string) File::get($source));

        $media = Media::query()->updateOrCreate(
            ['path' => $path],
            [
                'disk' => $disk,
                'original_name' => 'maps.png',
                'mime_type' => 'image/png',
                'file_size' => File::size($source),
                'width' => null,
                'height' => null,
                'alt_text' => 'Peta wilayah pelayanan paroki',
                'status' => MediaStatus::Pending,
                'uploader_id' => null,
                'error_message' => null,
            ],
        );

        GenerateImageVariants::dispatchSync($media->getKey());

        return $media->fresh()->load('variants');
    }

    /**
     * The service-area narrative as sanitized HTML.
     */
    private function contentHtml(): string
    {
        return '<p>Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau merupakan bagian dari Keuskupan Sintang yang melayani umat Katolik di Putussibau, Kabupaten Kapuas Hulu, Kalimantan Barat. Sebagai pusat kehidupan menggereja, paroki menjadi tempat bagi umat untuk merayakan Ekaristi, menerima pelayanan sakramen, memperdalam iman, serta membangun persaudaraan dalam Kristus.</p>'
            .'<h3>Cakupan Wilayah Pelayanan</h3>'
            .'<p>Pelayanan pastoral Paroki HSPMTB Putussibau menjangkau umat yang berada di wilayah Putussibau dan sekitarnya. Pelayanan ini dilaksanakan melalui kegiatan liturgi, pembinaan iman, pendampingan pastoral, serta berbagai kegiatan sosial dan kemasyarakatan.</p>'
            .'<p>Dalam menjalankan perutusannya, paroki hadir untuk mendampingi umat dalam berbagai tahap kehidupan, mulai dari pembinaan iman anak-anak dan kaum muda, pendampingan keluarga, hingga pelayanan kepada orang lanjut usia dan mereka yang membutuhkan perhatian khusus.</p>'
            .'<h3>Stasi sebagai Komunitas Umat</h3>'
            .'<p>Stasi merupakan komunitas umat Katolik yang menjadi bagian dari kehidupan pastoral paroki. Keberadaan stasi membantu mendekatkan pelayanan Gereja kepada umat, khususnya bagi mereka yang tinggal jauh dari gereja paroki.</p>'
            .'<p>Melalui kegiatan peribadatan, doa bersama, pembinaan iman, dan keterlibatan para pelayan pastoral, setiap stasi mengambil bagian dalam membangun kehidupan Gereja yang aktif dan saling mendukung. Hubungan antara paroki dan stasi menjadi bagian penting dalam menjaga kesatuan iman serta memperkuat persaudaraan antarumat.</p>'
            .'<p>Dokumentasi penelitian mengenai Paroki HSPMTB Putussibau mencatat delapan stasi, yaitu Jaras, Sungai Uluk, Kedamin Darat Hilir, Kedamin Darat Hulu, Pala Pulau, Sibau Hilir, Sibau Hulu, dan Tanjung Lasa. Daftar tersebut merupakan catatan historis dan perlu dikonfirmasi kembali kepada pihak paroki sebelum digunakan sebagai daftar stasi resmi yang berlaku saat ini.</p>'
            .'<h3>Kehidupan Umat dan Kebersamaan</h3>'
            .'<p>Kehidupan menggereja tidak hanya berlangsung di dalam gedung gereja, tetapi juga tumbuh dalam keluarga dan komunitas umat. Melalui doa bersama, pendalaman Kitab Suci, kegiatan lingkungan, serta berbagai bentuk pelayanan, umat diajak untuk menghidupi iman dalam kehidupan sehari-hari.</p>'
            .'<p>Kebersamaan antarumat juga menjadi kekuatan dalam menghadapi berbagai tantangan, menjaga keharmonisan, dan menumbuhkan kepedulian terhadap sesama. Setiap komunitas memiliki peran dalam mendukung kehidupan pastoral paroki sesuai dengan kebutuhan dan keadaan wilayahnya.</p>'
            .'<h3>Pelayanan Pastoral dan Sakramental</h3>'
            .'<p>Paroki memberikan pendampingan rohani kepada umat melalui perayaan Ekaristi, pelayanan sakramen, persiapan penerimaan sakramen, katekese, serta pendampingan keluarga. Pelayanan ini dilaksanakan dengan memperhatikan kebutuhan umat dan ketentuan Gereja Katolik.</p>'
            .'<p>Melalui kerja sama antara pastor, dewan pastoral, para pelayan Gereja, dan umat, pelayanan pastoral diharapkan dapat berjalan secara berkesinambungan dan menjangkau komunitas-komunitas yang menjadi bagian dari paroki.</p>'
            .'<h3>Bersatu dalam Iman dan Pelayanan</h3>'
            .'<p>Seluruh wilayah pelayanan Paroki HSPMTB Putussibau merupakan bagian dari satu persekutuan umat Allah. Perbedaan lokasi, keadaan, dan kebutuhan setiap komunitas menjadi kesempatan untuk saling membantu dan memperkuat persaudaraan.</p>'
            .'<p>Dengan semangat iman, solidaritas, dan partisipasi, Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau terus dipanggil untuk menghadirkan kasih Kristus di tengah umat dan masyarakat. Melalui keterlibatan seluruh umat, kehidupan Gereja diharapkan semakin bertumbuh dan menjadi berkat bagi sesama.</p>';
    }
}
