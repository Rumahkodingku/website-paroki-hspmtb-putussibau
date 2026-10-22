<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\ServiceFaq;
use App\Models\ServiceRequirement;
use App\Models\ServiceStep;
use Illuminate\Database\Seeder;

class DemoServicesSeeder extends Seeder
{
    public function run(Media $media): void
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
