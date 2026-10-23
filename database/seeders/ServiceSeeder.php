<?php

namespace Database\Seeders;

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceFaq;
use App\Models\ServiceRequirement;
use App\Models\ServiceStep;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class ServiceSeeder extends Seeder
{
    private const IMAGE = 'images.jpg';

    /**
     * @var list<array{
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
     *     requirements: list<array{text: string, note?: string|null, requirement_condition?: string|null, applies_to?: string|null}>,
     *     steps: list<array{title: string, description?: string|null}>,
     *     faqs: list<array{question: string, answer: string}>
     * }>
     */
    private const SERVICES = [
        [
            'slug' => 'baptis',
            'title' => 'Sakramen Baptis',
            'category' => 'inisiasi-kristiani',
            'summary' => 'Sakramen pintu masuk ke dalam Gereja Katolik, yang menghapus dosa asal dan dosa pribadi, menganugerahkan hidup baru dalam Kristus, serta menjadikan seseorang anggota Gereja.',
            'introduction' => '<p>Sakramen Baptis adalah fondasi seluruh kehidupan Kristiani. Melalui air dan firman, '
                .'seseorang dibersihkan dari dosa, dilahirkan kembali dari air dan Roh Kudus, serta diterima '
                .'sebagai anggota Gereja Katolik. Sakramen ini dapat diterima oleh bayi, anak-anak, maupun '
                .'orang dewasa yang mempersiapkan diri melalui katekumenat.</p>'
                .'<p>Paroki melayani baptis bagi bayi dan anak kecil dari keluarga Katolik, serta baptis '
                .'dewasa bagi mereka yang baru memilih menjadi Katolik. Orang tua, wali baptis, dan calon '
                .'baptis didampingi melalui pembekalan agar memahami makna dan tanggung jawab yang diemban.</p>',
            'schedule_info' => 'Pelaksanaan baptis disepakati bersama pastor dan sekretariat paroki. Pembekalan orang tua dan wali baptis mengikuti jadwal katekese paroki, sedangkan calon baptis dewasa mengikuti masa katekumenat sesuai jadwal pendampingan.',
            'fee_info' => null,
            'contact_name' => 'Sekretariat Paroki',
            'contact_phone' => '628110000001',
            'form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSfCONTOHbaptis/viewform',
            'is_form_open' => true,
            'form_closed_message' => null,
            'is_emergency' => false,
            'sort_order' => 1,
            'requirements' => [
                ['text' => 'Surat nikah gereja orang tua, atau keterangan khusus bila orang tua belum menikah secara gereja'],
                ['text' => 'Akta kelahiran atau surat keterangan lahir bayi/anak'],
                ['text' => 'Fotokopi Kartu Keluarga'],
                ['text' => 'Surat keterangan dari ketua lingkungan/wilayah'],
                ['text' => 'Data wali baptis beserta surat baptis dan krisma wali', 'note' => 'Wali baptis beragama Katolik, telah menerima Komuni dan Krisma, serta bersedia mendampingi pertumbuhan iman.'],
                ['text' => 'Pas foto sesuai ketentuan sekretariat', 'note' => 'Bila diminta.', 'requirement_condition' => 'upon_request'],
                ['text' => 'Surat permohonan menjadi Katolik bagi calon baptis dewasa', 'note' => 'Disertai keterangan pernah/belum dibaptis bila diperlukan.'],
            ],
            'steps' => [
                ['title' => 'Membaca syarat dan menyiapkan berkas', 'description' => 'Orang tua atau calon membaca syarat, mengisi pendaftaran, dan menyiapkan dokumen yang diperlukan.'],
                ['title' => 'Mengikuti pembekalan orang tua dan wali baptis', 'description' => 'Bagi baptis bayi/anak. Calon baptis dewasa mengikuti masa katekumenat.'],
                ['title' => 'Menentukan jadwal pelaksanaan bersama pastor/sekretariat'],
                ['title' => 'Pelaksanaan sakramen baptis'],
                ['title' => 'Pencatatan dan pengambilan surat baptis', 'description' => 'Setelah pencatatan di buku baptis paroki.'],
            ],
            'faqs' => [
                ['question' => 'Apakah orang tua wajib menikah secara gereja?', 'answer' => 'Pada umumnya ya. Bila belum, silakan menghubungi sekretariat untuk membicarakan langkah yang perlu disiapkan.'],
                ['question' => 'Siapa saja yang boleh menjadi wali baptis?', 'answer' => 'Wali baptis adalah umat Katolik yang telah menerima Komuni dan Krisma serta bersedia mendampingi pertumbuhan iman.'],
                ['question' => 'Apakah ada biaya?', 'answer' => 'Persembahan bersifat sukarela. Silakan menghubungi sekretariat untuk informasi lebih lanjut.'],
                ['question' => 'Berapa lama prosesnya?', 'answer' => 'Waktu bergantung pada jadwal pembekalan, masa katekumenat, dan kesepakatan pelaksanaan bersama pastor/sekretariat.'],
            ],
        ],
        [
            'slug' => 'penguatan-krisma',
            'title' => 'Sakramen Penguatan (Krisma)',
            'category' => 'inisiasi-kristiani',
            'summary' => 'Sakramen yang menyempurnakan anugerah Baptis, dengan dicurahkannya Roh Kudus yang memberi kekuatan untuk hidup sebagai saksi Kristus.',
            'introduction' => '<p>Sakramen Penguatan, atau Krisma, merupakan bagian dari inisiasi Katolik. '
                .'Melalui sakramen ini, anugerah Baptis disempurnakan: umat diperkuat oleh Roh Kudus '
                .'untuk hidup sebagai saksi Kristus dalam dunia. Peserta dipersiapkan melalui katekese '
                .'dan pendampingan sebelum perayaan yang dipimpin Uskup atau pastor yang diberi mandat.</p>'
                .'<p>Peserta adalah umat yang telah dibaptis dan mempersiapkan diri sesuai ketentuan paroki. '
                .'Setiap peserta menyiapkan wali krisma yang telah menerima Komuni dan Krisma.</p>',
            'schedule_info' => 'Mengikuti katekese persiapan Krisma sesuai jadwal paroki, termasuk pengakuan dosa dan persiapan akhir sebelum perayaan.',
            'fee_info' => null,
            'contact_name' => 'Sekretariat Paroki',
            'contact_phone' => '628110000004',
            'form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSfCONTOHkrisma/viewform',
            'is_form_open' => false,
            'form_closed_message' => 'Pendaftaran Krisma untuk periode ini sudah ditutup. Hubungi sekretariat untuk informasi periode berikutnya.',
            'is_emergency' => false,
            'sort_order' => 2,
            'requirements' => [
                ['text' => 'Fotokopi surat baptis dan bukti Komuni Pertama'],
                ['text' => 'Fotokopi Kartu Keluarga'],
                ['text' => 'Surat keterangan telah mengikuti katekese persiapan Krisma'],
                ['text' => 'Data wali krisma beserta surat baptis/krisma wali'],
                ['text' => 'Surat pengantar dari lingkungan', 'note' => 'Bila diminta.', 'requirement_condition' => 'upon_request'],
            ],
            'steps' => [
                ['title' => 'Mengisi pendaftaran peserta'],
                ['title' => 'Mengikuti katekese persiapan Krisma sesuai jadwal'],
                ['title' => 'Pengakuan dosa dan persiapan akhir'],
                ['title' => 'Perayaan sakramen Krisma'],
            ],
            'faqs' => [
                ['question' => 'Siapa yang dapat menerima Krisma?', 'answer' => 'Umat yang telah dibaptis dan mempersiapkan diri sesuai ketentuan paroki.'],
                ['question' => 'Apakah perlu wali krisma?', 'answer' => 'Ya, peserta menyiapkan data wali krisma sesuai ketentuan paroki.'],
                ['question' => 'Kapan perayaan Krisma diadakan?', 'answer' => 'Perayaan mengikuti jadwal Uskup atau pastor yang diberi mandat. Informasi akan disampaikan saat persiapan.'],
                ['question' => 'Apakah pendaftaran selalu dibuka?', 'answer' => 'Pendaftaran dibuka pada periode tertentu. Bila ditutup, pengumuman akan tampil pada halaman ini.'],
            ],
        ],
        [
            'slug' => 'ekaristi',
            'title' => 'Sakramen Ekaristi',
            'category' => 'inisiasi-kristiani',
            'summary' => 'Sakramen sumber dan puncak kehidupan Kristiani, di mana Kristus hadir nyata dalam roti dan anggur yang dikuduskan, memelihara umat dalam perjalanan iman.',
            'introduction' => '<p>Sakramen Ekaristi adalah sumber dan puncak seluruh kehidupan Kristiani. '
                .'Dalam perayaan Ekaristi, Kristus hadir secara nyata dalam rupa roti dan anggur yang '
                .'dikuduskan, dan umat dipersatukan dengan-Nya serta dengan sesama. Sakramen ini '
                .'memelihara umat dalam perjalanan iman dan menguatkan mereka untuk hidup sesuai '
                .'Injil.</p>'
                .'<p>Paroki melayani persiapan Komuni Pertama bagi anak-anak yang telah siap mengikuti '
                .'katekese, serta pendampingan bagi umat yang ingin memperdalam pemahaman tentang '
                .'Ekaristi. Informasi jadwal dan persiapan disampaikan melalui sekretariat paroki.</p>',
            'schedule_info' => 'Persiapan Komuni Pertama mengikuti jadwal pelajaran katekese, termasuk pengakuan dosa pertama dan gladi bersama. Pelaksanaan Ekaristi dalam misa harian dan mingguan mengikuti jadwal misa paroki.',
            'fee_info' => null,
            'contact_name' => 'Sekretariat Paroki',
            'contact_phone' => '628110000003',
            'form_url' => null,
            'is_form_open' => false,
            'form_closed_message' => null,
            'is_emergency' => false,
            'sort_order' => 3,
            'requirements' => [
                ['text' => 'Fotokopi surat baptis'],
                ['text' => 'Fotokopi Kartu Keluarga'],
                ['text' => 'Surat keterangan telah mengikuti katekese/persiapan Komuni Pertama'],
                ['text' => 'Persetujuan orang tua/wali'],
                ['text' => 'Surat keterangan telah mengikuti persiapan Ekaristi bagi umat dewasa', 'note' => 'Bila diperlukan.'],
            ],
            'steps' => [
                ['title' => 'Pendaftaran dan tindak lanjut oleh sekretariat/pembina katekese'],
                ['title' => 'Mengikuti pelajaran persiapan', 'description' => 'Termasuk pengakuan dosa pertama sesuai jadwal.'],
                ['title' => 'Gladi/latihan bersama'],
                ['title' => 'Perayaan Komuni Pertama dalam misa yang ditentukan'],
            ],
            'faqs' => [
                ['question' => 'Untuk usia berapa Komuni Pertama disiapkan?', 'answer' => 'Umumnya bagi anak yang telah siap mengikuti katekese persiapan. Silakan menghubungi sekretariat untuk ketentuannya.'],
                ['question' => 'Apakah orang tua ikut mendampingi?', 'answer' => 'Ya, orang tua/wali mendampingi anak selama masa persiapan.'],
                ['question' => 'Apakah pendaftaran selalu dibuka?', 'answer' => 'Pendaftaran dibuka pada periode tertentu. Bila ditutup, pengumuman akan tampil pada halaman ini.'],
                ['question' => 'Bagaimana bila umat dewasa ingin menerima Ekaristi pertama kali?', 'answer' => 'Silakan menghubungi pastor paroki/sekretariat untuk pendampingan dan persiapan yang sesuai.'],
            ],
        ],
        [
            'slug' => 'tobat-rekonsiliasi',
            'title' => 'Sakramen Tobat dan Rekonsiliasi',
            'category' => 'penyembuhan',
            'summary' => 'Sakramen pengampunan dosa, yang mendamaikan umat dengan Tuhan dan Gereja melalui pertobatan tulus dan pengampunan sakramental.',
            'introduction' => '<p>Sakramen Tobat dan Rekonsiliasi adalah sakramen penyembuhan yang '
                .'mendamaikan umat dengan Tuhan dan Gereja. Melalui pengakuan dosa, pertobatan tulus, '
                .'dan pengampunan sakramental, umat dipulihkan dalam persekutuan dengan Kristus. '
                .'Sakramen ini mengundang setiap orang untuk bertobat dan memperbarui hidup.</p>'
                .'<p>Paroki menyediakan jadwal pengakuan dosa rutin serta pelayanan pengakuan dosa '
                .'secara pribadi dengan pastor. Umat dapat menghubungi sekretariat untuk informasi '
                .'jadwal dan tata cara yang berlaku.</p>',
            'schedule_info' => 'Pengakuan dosa rutin mengikuti jadwal yang diumumkan paroki. Pengakuan dosa pribadi dapat diatur dengan pastor melalui sekretariat.',
            'fee_info' => null,
            'contact_name' => 'Sekretariat Paroki',
            'contact_phone' => '628110000007',
            'form_url' => null,
            'is_form_open' => false,
            'form_closed_message' => null,
            'is_emergency' => false,
            'sort_order' => 4,
            'requirements' => [
                ['text' => 'Tidak ada syarat dokumen khusus'],
                ['text' => 'Kesiapan hati untuk mengakui dosa dan bertobat'],
                ['text' => 'Pengetahuan dasar tentang pemeriksaan batin', 'note' => 'Pembinaan singkat dapat diberikan oleh pastor bila diperlukan.'],
            ],
            'steps' => [
                ['title' => 'Mempersiapkan diri melalui pemeriksaan batin'],
                ['title' => 'Mengikuti pengakuan dosa sesuai jadwal paroki'],
                ['title' => 'Melakukan pertobatan dan penitensi yang diberikan'],
                ['title' => 'Menerima pengampunan dan berdamai dengan Tuhan dan Gereja'],
            ],
            'faqs' => [
                ['question' => 'Apakah pengakuan dosa wajib?', 'answer' => 'Umat Katolik diwajibkan mengaku dosa berat setidaknya sekali setahun dan sebelum menerima Komuni Kudus bila menyadari dosa berat.'],
                ['question' => 'Apakah bisa mengaku dosa di luar jadwal?', 'answer' => 'Ya, dapat diatur dengan pastor melalui sekretariat paroki.'],
                ['question' => 'Apakah pengakuan dosa bersifat rahasia?', 'answer' => 'Ya, kerahasiaan pengakuan dosa dijamin oleh Gereja.'],
                ['question' => 'Apakah ada biaya?', 'answer' => 'Tidak ada biaya. Persembahan bersifat sukarela.'],
            ],
        ],
        [
            'slug' => 'pengurapan-orang-sakit',
            'title' => 'Sakramen Pengurapan Orang Sakit',
            'category' => 'penyembuhan',
            'summary' => 'Sakramen bagi umat yang sakit berat atau lanjut usia, yang memberikan penghiburan, pengampunan, dan pengudusan dalam penderitaan.',
            'introduction' => '<p>Sakramen Pengurapan Orang Sakit diberikan kepada umat Katolik yang sakit '
                .'berat atau lanjut usia. Sakramen ini memberikan penghiburan, pengampunan dosa, '
                .'pengudusan dalam penderitaan, dan persatuan dengan penderitaan Kristus. '
                .'Keluarga atau ketua lingkungan dapat menghubungi nomor pelayanan darurat paroki '
                .'kapan saja.</p>'
                .'<p>Pelayanan ini dilakukan dengan kunjungan pastor atau petugas pastoral ke rumah '
                .'sakit atau rumah orang sakit, disertai doa dan perayaan sakramen sesuai kondisi.</p>',
            'schedule_info' => 'Dilayani sesuai kebutuhan dan kondisi orang sakit, setelah keluarga menghubungi nomor pelayanan darurat paroki.',
            'fee_info' => null,
            'contact_name' => 'Petugas Pelayanan Orang Sakit',
            'contact_phone' => '628110000006',
            'form_url' => null,
            'is_form_open' => false,
            'form_closed_message' => null,
            'is_emergency' => true,
            'sort_order' => 5,
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
                ['question' => 'Apakah sakramen ini hanya untuk orang yang sekarat?', 'answer' => 'Tidak. Sakramen ini juga diberikan kepada umat yang sakit berat atau lanjut usia, tidak hanya menjelang kematian.'],
            ],
        ],
        [
            'slug' => 'tahbisan-suci',
            'title' => 'Sakramen Tahbisan Suci',
            'category' => 'pelayanan-persekutuan',
            'summary' => 'Sakramen yang menahbiskan pria untuk melayani Gereja sebagai uskup, imam, atau diakon, demi kepentingan persekutuan umat.',
            'introduction' => '<p>Sakramen Tahbisan Suci adalah sakramen yang menahbiskan pria untuk melayani '
                .'Gereja sebagai uskup, imam, atau diakon. Melalui sakramen ini, Kristus '
                .'memberikan rahmat dan tanda sakramental kepada mereka yang dipanggil untuk '
                .'menggembalakan umat dan memimpin perayaan sakramen. Sakramen ini bukan untuk '
                .'kepentingan pribadi, melainkan untuk kepentingan persekutuan umat.</p>'
                .'<p>Proses persiapan dan pembinaan tahbisan diatur oleh keuskupan. Paroki '
                .'mendampingi calon yang merasa dipanggil dengan doa, bimbingan, dan informasi '
                .'tentang tahapan yang berlaku.</p>',
            'schedule_info' => 'Persiapan dan pembinaan tahbisan mengikuti ketentuan keuskupan. Informasi dapat diperoleh melalui pastor paroki/sekretariat.',
            'fee_info' => null,
            'contact_name' => 'Sekretariat Paroki',
            'contact_phone' => '628110000008',
            'form_url' => null,
            'is_form_open' => false,
            'form_closed_message' => null,
            'is_emergency' => false,
            'sort_order' => 6,
            'requirements' => [
                ['text' => 'Surat permohonan dan rekomendasi dari pastor paroki'],
                ['text' => 'Dokumen administrasi sesuai ketentuan keuskupan'],
                ['text' => 'Hasil pembinaan dan penilaian rohani'],
                ['text' => 'Surat keterangan kesehatan dan psikologis', 'note' => 'Bila diminta oleh keuskupan.', 'requirement_condition' => 'upon_request'],
            ],
            'steps' => [
                ['title' => 'Berkonsultasi dengan pastor paroki'],
                ['title' => 'Mengikuti pembinaan dan tahapan persiapan keuskupan'],
                ['title' => 'Penilaian dan rekomendasi dari pihak berwenang'],
                ['title' => 'Perayaan sakramen tahbisan'],
            ],
            'faqs' => [
                ['question' => 'Siapa yang dapat menerima tahbisan?', 'answer' => 'Pria yang merasa dipanggil dan memenuhi ketentuan Gereja serta keuskupan.'],
                ['question' => 'Apakah paroki menerima pendaftaran langsung?', 'answer' => 'Paroki membantu informasi dan pendampingan awal; proses resmi diatur oleh keuskupan.'],
                ['question' => 'Apakah ada biaya?', 'answer' => 'Tidak ada biaya pendaftaran. Informasi lain dapat ditanyakan ke sekretariat.'],
                ['question' => 'Berapa lama prosesnya?', 'answer' => 'Lamanya mengikuti tahapan pembinaan yang ditetapkan keuskupan.'],
            ],
        ],
        [
            'slug' => 'perkawinan',
            'title' => 'Sakramen Perkawinan',
            'category' => 'pelayanan-persekutuan',
            'summary' => 'Sakramen yang menguduskan ikatan cinta kasih suami-istri, menjadikan keluarga sebagai gereja kecil yang hidup dalam iman.',
            'introduction' => '<p>Pasangan yang hendak menikah secara Katolik didampingi sejak jauh hari, '
                .'mulai dari pemeriksaan kanonik, kursus persiapan perkawinan, hingga pelaksanaan '
                .'pemberkatan. Sakramen Perkawinan menguduskan ikatan cinta kasih suami-istri dan '
                .'menjadikan keluarga sebagai gereja kecil.</p>'
                .'<p>Paroki mendampingi pasangan dengan memastikan persiapan rohani, administratif, '
                .'dan pastoral diselesaikan sebelum perayaan pemberkatan nikah.</p>',
            'schedule_info' => 'Hubungi pastor paroki/sekretariat paling lambat beberapa bulan sebelum rencana pernikahan.',
            'fee_info' => 'Biaya administrasi dan persembahan mengikuti ketentuan paroki. Silakan menghubungi sekretariat untuk rinciannya.',
            'contact_name' => 'Sekretariat Paroki',
            'contact_phone' => '628110000005',
            'form_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSfCONTOHpernikahan/viewform',
            'is_form_open' => true,
            'form_closed_message' => null,
            'is_emergency' => false,
            'sort_order' => 7,
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
    ];

    public function run(): void
    {
        $media = $this->dummyMedia();

        foreach (self::SERVICES as $service) {
            $category = $this->categoryFor($service['category']);

            $model = Service::withTrashed()->firstOrCreate(
                ['slug' => $service['slug']],
                [
                    'title' => $service['title'],
                    'service_category_id' => $category->id,
                    'thumbnail_media_id' => $media->getKey(),
                    'thumbnail_alt' => 'Ilustrasi layanan '.$service['title'].'.',
                    'summary' => $service['summary'],
                    'introduction' => $service['introduction'],
                    'audience_info' => $this->detailContent($service['slug'])['audience_info'],
                    'preparation_info' => $this->detailContent($service['slug'])['preparation_info'],
                    'schedule_info' => $service['schedule_info'],
                    'fee_info' => $service['fee_info'] ?? 'Persembahan bersifat sukarela. Silakan menghubungi sekretariat paroki untuk informasi lebih lanjut.',
                    'contact_name' => $service['contact_name'],
                    'contact_phone' => $service['contact_phone'],
                    'form_url' => $service['form_url'],
                    'form_button_label' => 'Daftar Sekarang',
                    'is_form_open' => $service['is_form_open'],
                    'form_closed_message' => $service['form_closed_message'] ?? ($service['form_url'] !== null
                        ? 'Pendaftaran sedang ditutup. Hubungi sekretariat untuk informasi periode berikutnya.'
                        : 'Layanan ini tidak menggunakan pendaftaran online. Silakan hubungi sekretariat paroki.'),
                    'is_emergency' => $service['is_emergency'],
                    'is_active' => true,
                    'sort_order' => $service['sort_order'],
                ],
            );

            if ($model->wasRecentlyCreated) {
                $this->replaceServiceChildren(
                    $model,
                    $service['requirements'],
                    $service['steps'],
                    $service['faqs'],
                );
            }
        }
    }

    private function dummyMedia(): Media
    {
        $source = public_path(self::IMAGE);

        if (! File::exists($source)) {
            throw new \RuntimeException('Berkas '.self::IMAGE.' tidak ditemukan di folder public/.');
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

    private function categoryFor(string $slug): ServiceCategory
    {
        $existing = ServiceCategory::withTrashed()->where('slug', $slug)->first();

        if ($existing !== null) {
            return $existing;
        }

        return ServiceCategory::query()->create([
            'name' => str($slug)->replace('-', ' ')->title()->toString(),
            'slug' => $slug,
            'is_active' => true,
        ]);
    }

    /**
     * @param  list<array{text: string, note?: string|null, requirement_condition?: string|null, applies_to?: string|null}>  $requirements
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
                'note' => $requirement['note'] ?? 'Tidak ada catatan tambahan.',
                'requirement_condition' => $requirement['requirement_condition'] ?? 'required',
                'applies_to' => $requirement['applies_to'] ?? 'Umum',
                'sort_order' => $index,
            ]);
        }

        foreach ($steps as $index => $step) {
            ServiceStep::query()->create([
                'service_id' => $service->getKey(),
                'title' => $step['title'],
                'description' => $step['description'] ?? 'Ikuti tahapan ini sesuai arahan pastor/sekretariat paroki.',
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

    /**
     * Konten sasaran & persiapan yang diturunkan dari data existing (heuristik).
     * Menunggu persetujuan paroki sebelum dianggap resmi.
     *
     * @return array{audience_info: string|null, preparation_info: string|null}
     */
    private function detailContent(string $slug): array
    {
        return match ($slug) {
            'baptis' => [
                'audience_info' => 'Bayi dan anak kecil dari keluarga Katolik, serta calon baptis dewasa yang mempersiapkan diri melalui katekumenat; juga orang tua dan wali baptis.',
                'preparation_info' => 'Orang tua dan wali baptis mengikuti pembekalan; calon baptis dewasa mengikuti masa katekumenat. Siapkan dokumen yang diminta dan tentukan jadwal bersama pastor/sekretariat.',
            ],
            'penguatan-krisma' => [
                'audience_info' => 'Umat yang telah dibaptis dan mempersiapkan diri sesuai ketentuan paroki, beserta wali krisma.',
                'preparation_info' => 'Mengikuti katekese persiapan Krisma, pengakuan dosa, dan persiapan akhir sebelum perayaan.',
            ],
            'ekaristi' => [
                'audience_info' => 'Anak-anak yang siap mengikuti katekese Komuni Pertama, serta umat dewasa yang ingin memperdalam pemahaman Ekaristi.',
                'preparation_info' => 'Mengikuti pelajaran persiapan (termasuk pengakuan dosa pertama), gladi bersama, dan perayaan Komuni Pertama.',
            ],
            'tobat-rekonsiliasi' => [
                'audience_info' => 'Seluruh umat yang ingin menerima pengampunan dosa dan berdamai dengan Tuhan dan Gereja.',
                'preparation_info' => 'Mempersiapkan diri melalui pemeriksaan batin; pengakuan dosa rutin atau pribadi sesuai jadwal paroki.',
            ],
            'pengurapan-orang-sakit' => [
                'audience_info' => 'Umat Katolik yang sakit berat atau lanjut usia, beserta keluarga pendamping.',
                'preparation_info' => 'Hubungi nomor pelayanan darurat paroki; siapkan data dasar dan lokasi orang sakit.',
            ],
            'tahbisan-suci' => [
                'audience_info' => 'Pria yang merasa dipanggil untuk ditahbiskan sebagai uskup, imam, atau diakon.',
                'preparation_info' => 'Berkonsultasi dengan pastor paroki; mengikuti pembinaan dan tahapan persiapan keuskupan.',
            ],
            'perkawinan' => [
                'audience_info' => 'Pasangan calon suami-istri yang hendak menikah secara Katolik.',
                'preparation_info' => 'Hubungi pastor/sekretariat beberapa bulan sebelum rencana; pemeriksaan kanonik, kursus persiapan, pengumuman.',
            ],
            default => [
                'audience_info' => null,
                'preparation_info' => null,
            ],
        };
    }
}
