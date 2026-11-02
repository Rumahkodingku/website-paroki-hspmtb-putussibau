<?php

namespace Database\Seeders\Demo;

use App\Enums\AreaType;
use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Area;
use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds the Area module with real parish station data and a first
 * lingkungan list based on public sources.
 *
 * Demo-only: called by DemoContentSeeder when config('demo.enabled') is true.
 * Existing areas are removed first so the seeder is idempotent and the
 * sort order stays clean.
 */
class DemoAreaSeeder extends Seeder
{
    private const IMAGE = 'illustrations/demo/thumbnail-16-9.png';

    public function run(): void
    {
        $media = $this->dummyMedia();

        // Start clean. Children are removed first because areas.parent_id is
        // RESTRICT, then the parents can be force-deleted.
        Area::withTrashed()->whereNotNull('parent_id')->forceDelete();
        Area::withTrashed()->forceDelete();

        $sort = 1;

        foreach ($this->stasi() as $item) {
            Area::query()->create([
                'parent_id' => null,
                'name' => $item['name'],
                'slug' => Str::slug($item['name']),
                'type' => AreaType::Stasi,
                'description' => $this->plainText($item['description']),
                'photo_media_id' => $media->getKey(),
                'show_contact' => false,
                'sort_order' => $sort++,
                'is_active' => true,
            ]);
        }

        $sort = 1;

        foreach ($this->lingkungan() as $name) {
            Area::query()->create([
                'parent_id' => null,
                'name' => $name,
                'slug' => Str::slug($name),
                'type' => AreaType::Lingkungan,
                'description' => $this->plainText($this->lingkunganDescription($name)),
                'photo_media_id' => $media->getKey(),
                'show_contact' => false,
                'sort_order' => $sort++,
                'is_active' => true,
            ]);
        }
    }

    private function dummyMedia(): Media
    {
        $source = public_path(self::IMAGE);

        if (! File::exists($source)) {
            throw new \RuntimeException('Berkas '.self::IMAGE.' tidak ditemukan di folder public/.');
        }

        $disk = (string) config('media.original_disk');
        $path = 'media/originals/dummy-area.png';

        Storage::disk($disk)->makeDirectory(dirname($path));
        Storage::disk($disk)->put($path, (string) File::get($source));

        $media = Media::query()->updateOrCreate(
            ['path' => $path],
            [
                'disk' => $disk,
                'original_name' => 'thumbnail-16-9.png',
                'mime_type' => 'image/png',
                'file_size' => File::size($source),
                'width' => null,
                'height' => null,
                'alt_text' => 'Foto area paroki',
                'status' => MediaStatus::Pending,
                'uploader_id' => null,
                'error_message' => null,
            ],
        );

        GenerateImageVariants::dispatchSync($media->getKey());

        return $media->fresh()->load('variants');
    }

    /**
     * @return list<array{name: string, description: string}>
     */
    private function stasi(): array
    {
        return [
            ['name' => 'Stasi Jaras', 'description' => '<p>Stasi Jaras merupakan salah satu komunitas umat Katolik yang tercatat dalam wilayah pelayanan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau, Keuskupan Sintang. Sebagai bagian dari kehidupan menggereja, stasi ini menjadi tempat bagi umat untuk membangun persaudaraan, mengembangkan iman, serta berpartisipasi dalam kegiatan pastoral dan liturgi sesuai dengan jadwal pelayanan paroki.</p><p>Melalui keterlibatan umat dan para pelayan Gereja, Stasi Jaras mengambil bagian dalam mewujudkan kehidupan menggereja yang aktif dan saling mendukung.</p>'],
            ['name' => 'Stasi Sungai Uluk', 'description' => '<p>Stasi Sungai Uluk merupakan salah satu stasi yang termasuk dalam dokumentasi wilayah pelayanan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau. Komunitas umat di stasi ini menjadi bagian dari persekutuan umat Katolik yang bertumbuh dalam iman, doa, dan persaudaraan.</p><p>Melalui kegiatan liturgi, pembinaan iman, dan kerja sama dengan pihak paroki, pelayanan stasi mendukung pertumbuhan rohani umat serta keterlibatan mereka dalam kehidupan Gereja.</p>'],
            ['name' => 'Stasi Kedamin Darat Hilir', 'description' => '<p>Stasi Kedamin Darat Hilir merupakan salah satu komunitas umat Katolik yang tercatat dalam wilayah pelayanan Paroki HSPMTB Putussibau. Stasi ini menjadi bagian dari kehidupan pastoral paroki dalam mendukung perayaan liturgi, pembinaan iman, dan penguatan persaudaraan antarumat.</p><p>Melalui kebersamaan dan partisipasi umat, pelayanan stasi diharapkan terus berkembang sesuai dengan kebutuhan rohani komunitas setempat.</p>'],
            ['name' => 'Stasi Kedamin Darat Hulu', 'description' => '<p>Stasi Kedamin Darat Hulu merupakan salah satu komunitas umat Katolik dalam cakupan pelayanan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau. Kehadiran stasi menjadi bagian dari upaya mendekatkan kehidupan menggereja kepada umat melalui kegiatan doa, liturgi, dan pembinaan iman.</p><p>Dengan semangat persaudaraan dan keterlibatan umat, stasi ini mengambil bagian dalam perjalanan pastoral bersama Paroki HSPMTB Putussibau.</p>'],
            ['name' => 'Stasi Pala Pulau', 'description' => '<p>Stasi Pala Pulau merupakan salah satu komunitas umat Katolik yang tercatat dalam wilayah pelayanan Paroki HSPMTB Putussibau. Komunitas ini menjadi bagian dari persekutuan umat yang bersama-sama mengembangkan iman, mempererat persaudaraan, dan mendukung kegiatan pastoral Gereja.</p><p>Melalui partisipasi umat dan kerja sama dengan paroki, Stasi Pala Pulau diharapkan terus menjadi tempat bertumbuhnya kehidupan iman dan kepedulian terhadap sesama.</p>'],
            ['name' => 'Stasi Sibau Hilir', 'description' => '<p>Stasi Sibau Hilir merupakan salah satu stasi yang tercatat dalam dokumentasi wilayah pelayanan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau. Sebagai komunitas umat Katolik, stasi ini menjadi bagian dari kehidupan Gereja melalui persekutuan, kegiatan liturgi, dan pendampingan iman.</p><p>Keterlibatan umat dalam kehidupan stasi mendukung persaudaraan serta keberlanjutan pelayanan pastoral di wilayah paroki.</p>'],
            ['name' => 'Stasi Sibau Hulu', 'description' => '<p>Stasi Sibau Hulu merupakan salah satu komunitas umat Katolik yang tercatat dalam wilayah pelayanan Paroki HSPMTB Putussibau. Stasi ini menjadi bagian dari persekutuan umat yang dipanggil untuk bertumbuh dalam iman, membangun kebersamaan, dan berpartisipasi dalam kehidupan menggereja.</p><p>Melalui pelayanan pastoral dan keterlibatan umat, komunitas stasi diharapkan terus memperkuat persaudaraan serta menghadirkan nilai-nilai Injil dalam kehidupan sehari-hari.</p>'],
            ['name' => 'Stasi Tanjung Lasa', 'description' => '<p>Stasi Tanjung Lasa merupakan salah satu stasi dalam dokumentasi wilayah pelayanan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau. Penelitian mengenai paroki mencatat Tanjung Lasa sebagai salah satu wilayah stasi yang berada dalam cakupan pelayanan pastoral paroki.</p><p>Melalui kegiatan liturgi, pembinaan iman, dan partisipasi umat, stasi ini menjadi bagian dari upaya bersama untuk menjaga kehidupan menggereja dan mempererat persaudaraan umat Katolik.</p>'],
        ];
    }

    /**
     * @return list<string>
     */
    private function lingkungan(): array
    {
        return [
            'Lingkungan Santo Stefanus',
            'Lingkungan Santa Theresia',
            'Lingkungan Santo Fransiskus',
            'Lingkungan Santo Antonius',
            'Lingkungan Santo Yosep',
            'Lingkungan Ratu Rosari',
            'Lingkungan Yohanes Rasul Teluk Aur',
            'Lingkungan Santo Thomas Aquino',
        ];
    }

    private function lingkunganDescription(string $name): string
    {
        return '<p>'.$name.' merupakan salah satu komunitas umat Katolik yang tercatat dalam kehidupan menggereja di pusat Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau. Lingkungan menjadi tempat bagi umat untuk membangun kebersamaan, memperdalam iman, serta mendukung kegiatan pastoral dan liturgi paroki.</p>'
            .'<p>Melalui doa bersama, keterlibatan dalam perayaan Gereja, dan kepedulian terhadap sesama, umat lingkungan mengambil bagian dalam membangun kehidupan menggereja yang aktif, bersaudara, dan saling mendukung.</p>';
    }

    /**
     * Convert a small HTML fragment to plain text.
     */
    private function plainText(string $html): string
    {
        $text = str_replace(['</p>', '<br>', '<br/>', '<br />'], ["\n\n", "\n", "\n", "\n"], $html);
        $text = strip_tags($text);
        $text = preg_replace('/[ \t]+/', ' ', $text);
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }
}
