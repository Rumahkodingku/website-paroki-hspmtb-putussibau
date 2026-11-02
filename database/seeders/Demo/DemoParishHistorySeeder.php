<?php

namespace Database\Seeders\Demo;

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Models\ParishHistoryItem;
use App\Models\ParishHistoryNarrative;
use App\Services\HtmlSanitizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

// Seeder untuk sejarah paroki
class DemoParishHistorySeeder extends Seeder
{
    private const IMAGE = 'illustrations/demo/thumbnail-16-9.png';

    public function run(): void
    {
        $media = $this->dummyMedia();

        ParishHistoryNarrative::query()->updateOrCreate(
            [],
            [
                'narrative' => app(HtmlSanitizer::class)->clean($this->narrativeHtml()),
            ],
        );

        /** @var list<array{title: string, year: int, date: string|null, description: string, alt: string, sort: int}> $items */
        $items = $this->items();

        foreach ($items as $item) {
            ParishHistoryItem::query()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'year' => $item['year'],
                    'date' => $item['date'],
                    'description' => app(HtmlSanitizer::class)->clean($item['description']),
                    'media_id' => $media->getKey(),
                    'image_alt' => $item['alt'],
                    'sort_order' => $item['sort'],
                    'is_published' => true,
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

    private function narrativeHtml(): string
    {
        return '<h2>Sejarah Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau</h2>'
            .'<h3>Awal Kehadiran Gereja Katolik di Putussibau</h3>'
            .'<p>Sejarah Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau tidak dapat dipisahkan dari perkembangan karya misi Katolik di wilayah Kapuas Hulu dan Kalimantan Barat. Kehadiran Gereja di wilayah ini bertumbuh melalui pelayanan para misionaris, pendampingan umat, serta berbagai karya sosial yang turut menjangkau masyarakat setempat.</p>'
            .'<p>Sebelum paroki resmi berdiri, pelayanan di Putussibau telah mulai berkembang. Pada tahun 1938, para suster dari Kongregasi Suster Fransiskanes dari Santo Antonius (SMFA) mulai memberikan pelayanan di Rumah Sakit pemerintah Hindia Belanda di Putussibau. Kehadiran mereka menjadi bagian dari perkembangan karya pelayanan Katolik di daerah tersebut.</p>'
            .'<p>Tonggak penting dalam sejarah paroki terjadi pada <strong>10 Juni 1939</strong>, ketika Pater Jan Linssen dari Serikat Maria Montfortan (SMM) mulai menetap di Putussibau. Tanggal tersebut kemudian diperingati sebagai hari berdirinya Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau. Paroki ini juga tercatat sebagai paroki pertama yang dibuka oleh para Montfortan dalam karya misi mereka di Indonesia.</p>'
            .'<h3>Perintisan dan Perkembangan Karya Misi</h3>'
            .'<p>Pada masa awal berdirinya, pelayanan paroki berlangsung dalam keadaan yang masih sederhana. Ketika Pater Jan Linssen mulai menetap di Putussibau, belum tersedia pastoran maupun gedung gereja yang memadai. Ia sempat tinggal di tempat penginapan milik pemerintah kolonial sebelum kemudian berpindah ke tempat tinggal lainnya.</p>'
            .'<p>Pada tahun 1940, karya misi Montfortan di wilayah ini memperoleh tambahan tenaga melalui kedatangan Pater Lambertus van Kessel dan Pater Josef Wintraecken. Kehadiran para misionaris tersebut menjadi bagian dari upaya memperkuat pelayanan Gereja di wilayah yang kemudian berkembang menjadi bagian dari Keuskupan Sintang.</p>'
            .'<p>Perjalanan karya misi juga menghadapi tantangan besar ketika Perang Dunia II berlangsung. Pada periode 1942–1946, para misionaris Montfortan bersama sejumlah misionaris lainnya di Kalimantan Barat mengalami pengasingan ke wilayah Kuching di bawah pemerintahan Jepang. Setelah kembali pada tahun 1946, pelayanan Gereja secara bertahap dilanjutkan dan dikembangkan kembali.</p>'
            .'<h3>Perkembangan Pelayanan dan Pendidikan</h3>'
            .'<p>Dalam perjalanan sejarahnya, Paroki HSPMTB Putussibau tidak hanya berperan sebagai tempat peribadatan, tetapi juga berkembang melalui pelayanan pendidikan dan pendampingan umat. Para imam yang bertugas melanjutkan karya pastoral dengan memperhatikan kebutuhan rohani serta perkembangan kehidupan masyarakat.</p>'
            .'<p>Pada dekade 1950-an, perhatian terhadap pendidikan menjadi salah satu bagian penting dari karya pelayanan. Catatan sejarah misi menyebutkan adanya upaya membuka sekolah menengah pertama serta memperkuat asrama putra di Putussibau. Upaya tersebut menjadi bagian dari perkembangan karya pendidikan Katolik di wilayah ini.</p>'
            .'<p>Seiring berjalannya waktu, pelayanan paroki terus dilanjutkan oleh para imam Montfortan dan para pelayan Gereja lainnya. Perubahan dan pergantian tenaga pastoral menjadi bagian dari perjalanan panjang paroki dalam mempertahankan serta mengembangkan kehidupan iman umat.</p>'
            .'<h3>Pemekaran Wilayah Pelayanan</h3>'
            .'<p>Perkembangan umat dan kebutuhan pelayanan pastoral turut mendorong penataan wilayah Gereja. Salah satu peristiwa penting adalah pemekaran Paroki Penampakan Tuhan (Epifania) Siut-Melapi dari Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.</p>'
            .'<p>Pemekaran tersebut ditetapkan melalui Surat Keputusan Nomor 197/Par/79 tertanggal <strong>24 Agustus 1979</strong>, yang dikeluarkan oleh Mgr. Isak Doera. Wilayah pelayanan paroki baru tersebut mencakup kawasan Putussibau Selatan dari Batang Suai hingga Tanjung Lokang.</p>'
            .'<p>Peristiwa ini merupakan bagian penting dari sejarah perkembangan Gereja Katolik di Kapuas Hulu karena menunjukkan adanya penataan pelayanan agar pendampingan pastoral dapat menjangkau umat di wilayah yang lebih luas.</p>'
            .'<h3>Gedung Bina Remaja dan Kehidupan Sosial</h3>'
            .'<p>Selain menjalankan pelayanan rohani, Paroki HSPMTB Putussibau juga memiliki sarana yang digunakan untuk kegiatan sosial dan kemasyarakatan. Salah satunya adalah Gedung Bina Remaja yang, menurut pemberitaan Pemerintah Kabupaten Kapuas Hulu pada tahun 2021, telah berdiri sejak tahun 1972.</p>'
            .'<p>Gedung tersebut memiliki peran penting sebagai tempat berlangsungnya berbagai kegiatan. Pemanfaatannya tidak terbatas bagi umat Katolik, tetapi juga ditujukan untuk mendukung kegiatan sosial dan kepentingan masyarakat Kabupaten Kapuas Hulu secara luas.</p>'
            .'<p>Pada tanggal 20 Agustus 2021, dilaksanakan pemancangan tiang pertama pembangunan gedung Bina Remaja yang baru. Peristiwa ini menjadi salah satu tonggak perkembangan sarana pelayanan dan kegiatan kemasyarakatan yang berkaitan dengan Paroki HSPMTB Putussibau.</p>'
            .'<h3>Perkembangan Gereja dan Kehidupan Umat</h3>'
            .'<p>Dalam perkembangannya, Paroki HSPMTB Putussibau terus menjadi pusat perayaan Ekaristi, pelayanan sakramen, pembinaan iman, dan kegiatan komunitas umat Katolik. Pelayanan tersebut didukung oleh keterlibatan para imam, dewan pastoral, kelompok kategorial, serta umat dari berbagai lingkungan dan stasi.</p>'
            .'<p>Data penelitian yang diterbitkan mengenai panorama Paroki HSPMTB Putussibau mencatat bahwa pada 31 Desember 2020, jumlah umat paroki mencapai 9.280 jiwa. Penelitian tersebut juga mencatat delapan stasi, yaitu Jaras, Sungai Uluk, Kedamin Darat Hilir, Kedamin Darat Hulu, Pala Pulau, Sibau Hilir, Sibau Hulu, dan Tanjung Lasa. Angka dan daftar tersebut merupakan dokumentasi historis dari penelitian, sehingga perlu diperbarui apabila akan digunakan sebagai data resmi terkini.</p>'
            .'<p>Pada tahun 2021, Paroki HSPMTB Putussibau memperingati ulang tahun ke-81. Perayaan tersebut disertai pelantikan Dewan Pastoral Paroki untuk periode 2021–2024 dan mengangkat tema “Umat Allah Paroki Putussibau Solider, Partisipasif dan Mandiri”. Tema ini menggambarkan semangat yang diusung dalam perayaan pada tahun tersebut, bukan pernyataan bahwa tema itu merupakan visi dan misi resmi paroki yang berlaku sepanjang masa.</p>'
            .'<p>Kehidupan menggereja terus berlanjut melalui perayaan liturgi, kegiatan organisasi umat, pembinaan generasi muda, serta pelayanan sosial. Pada Februari 2026, Wanita Katolik Republik Indonesia (WKRI) DPC HSPMTB Putussibau juga menyelenggarakan konferensi cabang yang menjadi bagian dari dinamika organisasi dan keterlibatan umat dalam kehidupan paroki.</p>'
            .'<h3>Melanjutkan Perutusan Gereja</h3>'
            .'<p>Perjalanan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau sejak 1939 merupakan bagian dari sejarah panjang perkembangan Gereja Katolik di Kapuas Hulu. Dari masa perintisan misi, tantangan zaman, perkembangan pendidikan, pemekaran wilayah, hingga penguatan sarana pelayanan dan kegiatan umat, paroki terus bertumbuh melalui keterlibatan berbagai generasi.</p>'
            .'<p>Dengan meneladani Bunda Maria dan berpegang pada iman kepada Yesus Kristus, Paroki HSPMTB Putussibau dipanggil untuk terus membangun persaudaraan, memperdalam kehidupan iman, serta menghadirkan kasih dan kepedulian bagi sesama. Sejarah ini menjadi warisan bagi umat untuk disyukuri, dipelajari, dan diteruskan kepada generasi berikutnya.</p>';
    }

    /**
     * @return list<array{title: string, year: int, date: string|null, description: string, alt: string, sort: int}>
     */
    private function items(): array
    {
        return [
            ['title' => 'Awal Pelayanan Suster SMFA di Putussibau', 'year' => 1938, 'date' => null, 'description' => '<p>Pada tahun 1938, para suster dari Kongregasi Suster Fransiskanes dari Santo Antonius (SMFA) mulai memberikan pelayanan di Rumah Sakit pemerintah Hindia Belanda di Putussibau. Kehadiran mereka merupakan bagian dari perkembangan karya pelayanan Katolik di wilayah tersebut dan mendahului pendirian resmi Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau pada tahun 1939.</p>', 'alt' => 'Para suster Katolik dalam kegiatan pelayanan kesehatan pada masa misi di Putussibau.', 'sort' => 1],
            ['title' => 'Pendirian Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau', 'year' => 1939, 'date' => '1939-06-10', 'description' => '<p>Tanggal 10 Juni 1939 menjadi tonggak penting dalam sejarah Gereja Katolik di Putussibau. Pada tanggal tersebut, Pater Jan Linssen dari Serikat Maria Montfortan (SMM) mulai menetap di Putussibau. Tanggal ini kemudian diperingati sebagai hari berdirinya Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.</p><p>Pada masa awal pelayanan, paroki belum memiliki pastoran maupun gedung gereja yang memadai. Pelayanan dimulai dengan sarana yang sederhana, tetapi menjadi dasar bagi perkembangan kehidupan menggereja umat Katolik di Putussibau dan sekitarnya.</p>', 'alt' => 'Dokumentasi historis awal karya misi Katolik di Putussibau.', 'sort' => 2],
            ['title' => 'Kedatangan Misionaris Montfortan untuk Memperkuat Karya Misi', 'year' => 1940, 'date' => null, 'description' => '<p>Pada tahun 1940, karya misi Serikat Maria Montfortan di wilayah Kalimantan Barat memperoleh tambahan tenaga melalui kedatangan Pater Lambertus van Kessel dan Pater Josef Wintraecken. Kehadiran para misionaris tersebut menjadi bagian dari upaya memperkuat pelayanan Gereja di wilayah yang kemudian berkembang menjadi bagian dari Keuskupan Sintang.</p>', 'alt' => 'Dokumentasi para misionaris Montfortan pada masa awal karya misi di Kalimantan Barat.', 'sort' => 3],
            ['title' => 'Tantangan Karya Misi pada Masa Perang Dunia II', 'year' => 1942, 'date' => null, 'description' => '<p>Pada periode 1942–1946, karya misi Katolik di Kalimantan Barat menghadapi tantangan besar akibat Perang Dunia II dan pendudukan Jepang. Menurut catatan sejarah misi Montfortan, para misionaris SMM bersama sejumlah misionaris lainnya di wilayah Kalimantan Barat mengalami pengasingan ke Kuching.</p><p>Masa tersebut menjadi bagian dari sejarah sulit yang memengaruhi perjalanan karya misi di kawasan ini. Informasi yang tersedia menjelaskan keadaan para misionaris secara regional dan tidak merinci seluruh dampak langsungnya terhadap kehidupan umat Paroki HSPMTB Putussibau.</p>', 'alt' => 'Dokumentasi sejarah para misionaris Katolik di Kalimantan Barat pada masa Perang Dunia II.', 'sort' => 4],
            ['title' => 'Pemulihan Karya Misi Setelah Masa Pengasingan', 'year' => 1946, 'date' => null, 'description' => '<p>Pada tahun 1946, para misionaris yang sebelumnya mengalami pengasingan mulai kembali dari Kuching. Pelayanan Gereja di Kalimantan Barat kemudian dilanjutkan secara bertahap.</p><p>Peristiwa ini merupakan bagian dari pemulihan karya misi setelah masa Perang Dunia II. Pemulihan tersebut menjadi latar penting bagi keberlanjutan pelayanan pastoral di wilayah Keuskupan Sintang, termasuk kawasan Putussibau.</p>', 'alt' => 'Dokumentasi para misionaris Katolik dalam pelayanan di Kalimantan Barat setelah Perang Dunia II.', 'sort' => 5],
            ['title' => 'Penguatan Pelayanan Pastoral Montfortan', 'year' => 1947, 'date' => null, 'description' => '<p>Pada awal tahun 1947, para misionaris Serikat Maria Montfortan mengambil alih pelayanan di sejumlah paroki yang sebelumnya dilayani oleh Kapusin. Perkembangan ini merupakan bagian dari penataan dan penguatan karya misi Katolik di wilayah yang kemudian dikenal sebagai Keuskupan Sintang.</p><p>Peristiwa tersebut menjadi bagian dari konteks sejarah pelayanan Gereja di Kalimantan Barat pada masa setelah Perang Dunia II.</p>', 'alt' => 'Dokumentasi para imam Montfortan dalam pelayanan pastoral di Kalimantan Barat.', 'sort' => 6],
            ['title' => 'Pengembangan Karya Pendidikan Katolik di Putussibau', 'year' => 1950, 'date' => null, 'description' => '<p>Pada dekade 1950-an, pengembangan pendidikan menjadi salah satu bagian penting dalam perjalanan pelayanan Gereja di Putussibau. Catatan sejarah Keuskupan Sintang menyebutkan bahwa para imam yang berkarya di paroki ini mengupayakan pembukaan sekolah menengah pertama serta penguatan asrama putra.</p><p>Upaya tersebut mencerminkan perhatian Gereja terhadap pendidikan dan pembinaan generasi muda, selain pelayanan rohani dan perayaan sakramen. Informasi lebih terperinci mengenai tahun pendirian sekolah, nama lembaga pendidikan, dan pihak yang terlibat perlu dilengkapi berdasarkan arsip paroki.</p>', 'alt' => 'Dokumentasi kegiatan pendidikan Katolik di Putussibau pada masa lampau.', 'sort' => 7],
            ['title' => 'Pemekaran Paroki Penampakan Tuhan Siut-Melapi', 'year' => 1979, 'date' => '1979-08-24', 'description' => '<p>Pada tanggal 24 Agustus 1979, wilayah Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau mengalami perkembangan penting melalui pemekaran Paroki Penampakan Tuhan (Epifania) Siut-Melapi.</p><p>Pemekaran tersebut ditetapkan melalui Surat Keputusan Nomor 197/Par/79 yang dikeluarkan oleh Mgr. Isak Doera. Wilayah pelayanan paroki baru tersebut mencakup kawasan Putussibau Selatan dari Batang Suai hingga Tanjung Lokang.</p><p>Peristiwa ini merupakan bagian penting dari sejarah perkembangan Gereja Katolik di Kapuas Hulu karena menunjukkan adanya penataan pelayanan agar pendampingan pastoral dapat menjangkau umat di wilayah yang lebih luas.</p>', 'alt' => 'Dokumentasi sejarah pemekaran wilayah pelayanan Gereja Katolik di Putussibau dan Siut-Melapi.', 'sort' => 8],
            ['title' => 'Berdirinya Gedung Bina Remaja Paroki Putussibau', 'year' => 1972, 'date' => null, 'description' => '<p>Gedung Bina Remaja yang berkaitan dengan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau tercatat telah berdiri sejak tahun 1972. Gedung ini memiliki peran dalam mendukung berbagai kegiatan sosial dan kemasyarakatan di Kabupaten Kapuas Hulu.</p><p>Menurut pemberitaan Pemerintah Kabupaten Kapuas Hulu pada tahun 2021, pemanfaatan gedung tersebut tidak terbatas bagi umat Katolik, tetapi juga ditujukan untuk mendukung kegiatan berbagai lapisan masyarakat.</p><p>Keberadaan Gedung Bina Remaja menjadi bagian dari sejarah sarana pelayanan paroki yang turut berkontribusi terhadap kehidupan sosial masyarakat Putussibau.</p>', 'alt' => 'Gedung Bina Remaja Paroki HSPMTB Putussibau.', 'sort' => 9],
            ['title' => 'Pemancangan Tiang Pertama Gedung Bina Remaja', 'year' => 2021, 'date' => '2021-08-20', 'description' => '<p>Pada tanggal 20 Agustus 2021, dilaksanakan pemancangan tiang pertama pembangunan Gedung Bina Remaja Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau. Kegiatan ini dihadiri oleh Bupati Kapuas Hulu Fransiskus Diaan, sejumlah anggota DPRD, serta jajaran perangkat daerah.</p><p>Pembangunan tersebut menjadi langkah pengembangan sarana yang selama ini digunakan untuk mendukung kegiatan sosial dan kemasyarakatan. Gedung Bina Remaja diharapkan dapat terus memberikan manfaat bagi umat Katolik dan masyarakat Kabupaten Kapuas Hulu secara luas.</p>', 'alt' => 'Pemancangan tiang pertama pembangunan Gedung Bina Remaja Paroki HSPMTB Putussibau pada 20 Agustus 2021.', 'sort' => 10],
            ['title' => 'Peringatan HUT ke-81 Paroki HSPMTB Putussibau', 'year' => 2021, 'date' => '2021-06-10', 'description' => '<p>Pada tanggal 10 Juni 2021, Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau memperingati ulang tahun ke-81 sejak pendiriannya pada tahun 1939.</p><p>Perayaan tersebut mengangkat tema “Umat Allah Paroki Putussibau Solider, Partisipasif dan Mandiri”. Dalam rangkaian kegiatan peringatan, dilaksanakan pula pelantikan Dewan Pastoral Paroki untuk periode 2021–2024.</p><p>Peringatan ini menjadi kesempatan untuk mensyukuri perjalanan pelayanan Gereja, memperkuat keterlibatan umat, dan meneguhkan semangat kebersamaan dalam menjalankan perutusan pastoral.</p>', 'alt' => 'Perayaan ulang tahun ke-81 Paroki HSPMTB Putussibau pada 10 Juni 2021.', 'sort' => 11],
            ['title' => 'Dokumentasi Data Umat dan Wilayah Pelayanan Paroki', 'year' => 2020, 'date' => '2020-12-31', 'description' => '<p>Penelitian mengenai panorama Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau mencatat bahwa hingga 31 Desember 2020, jumlah umat paroki mencapai 9.280 jiwa. Penelitian tersebut juga mendokumentasikan delapan stasi, yakni Jaras, Sungai Uluk, Kedamin Darat Hilir, Kedamin Darat Hulu, Pala Pulau, Sibau Hilir, Sibau Hulu, dan Tanjung Lasa.</p><p>Data tersebut memberikan gambaran historis mengenai jumlah umat dan cakupan pelayanan paroki pada masa penelitian. Angka dan daftar stasi ini merupakan data pada periode tersebut, bukan konfirmasi atas kondisi terkini.</p>', 'alt' => 'Dokumentasi kegiatan umat dan wilayah pelayanan Paroki HSPMTB Putussibau.', 'sort' => 12],
            ['title' => 'Konferensi Cabang X WKRI DPC HSPMTB Putussibau', 'year' => 2026, 'date' => '2026-02-07', 'description' => '<p>Pada tanggal 7 Februari 2026, Wanita Katolik Republik Indonesia (WKRI) DPC HSPMTB Putussibau menyelenggarakan Konferensi Cabang X. Kegiatan ini menjadi bagian dari dinamika organisasi umat dan keterlibatan kaum perempuan Katolik dalam kehidupan menggereja.</p><p>Konferensi cabang merupakan kesempatan untuk memperkuat kebersamaan, mengevaluasi pelaksanaan kegiatan organisasi, serta melanjutkan peran WKRI dalam mendukung pelayanan dan kehidupan sosial masyarakat.</p>', 'alt' => 'Kegiatan Konferensi Cabang X WKRI DPC HSPMTB Putussibau pada 7 Februari 2026.', 'sort' => 13],
        ];
    }
}
