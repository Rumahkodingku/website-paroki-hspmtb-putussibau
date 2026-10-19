# PHASE 9A — Homepage / Beranda Publik

> **Status:** Implemented — engineering complete  
> **Prioritas:** High  
> **Bagian dari:** PHASE 9 — Homepage & Admin Dashboard  
> **Dokumen berikutnya:** `phase-09b-admin-dashboard.md` (dikerjakan setelah PHASE 9A selesai)  
> **Source of truth:** `docs/PRD.md`, `docs/ARCHITECTURE.md`, `docs/DESIGN.md`, `docs/DECISIONS.md`, serta implementasi aktual pada branch aktif.

---

## 1. Tujuan Dokumen

Dokumen ini adalah rencana implementasi untuk menyelesaikan **Beranda Publik** website Paroki Hati Santa Perawan Maria Tak Bernoda (HSPMTB) Putussibau.

AI agent wajib membaca dokumen ini beserta sumber yang disebutkan sebelum mengubah kode. Dokumen ini memisahkan audit, perencanaan, implementasi, dan verifikasi agar agent tidak langsung melakukan coding tanpa memahami arsitektur serta data yang sudah tersedia.

### 1.1 Tujuan produk

Beranda harus membantu umat dan pengunjung untuk:

1. Mengenali identitas Paroki HSPMTB Putussibau.
2. Menemukan jadwal Misa yang relevan dengan cepat.
3. Membaca berita dan informasi terbaru paroki.
4. Mengetahui agenda kegiatan mendatang.
5. Menemukan informasi pelayanan sakramen.
6. Melihat dokumentasi kegiatan paroki melalui galeri.
7. Menemukan alamat dan kanal komunikasi sekretariat.
8. Menjelajahi halaman publik lain melalui navigasi yang jelas.

### 1.2 Hasil yang diharapkan

- Placeholder Beranda pada route `/` diganti dengan halaman publik yang lengkap dan dinamis.
- Konten berasal dari Pengaturan Situs dan modul yang telah tersedia, bukan data dummy yang ditanam permanen di komponen.
- Layout mengikuti referensi visual homepage yang telah disetujui pengguna: bersih, lapang, hierarki visual jelas, aksen merah paroki, dan hero menggunakan foto gereja malam yang disediakan pengguna.
- Komponen navigasi, footer, SEO, kartu, dan section yang sudah ada digunakan kembali jika sesuai.
- Perubahan teruji melalui Pest, Vitest, type-check, lint/check, build, dan Playwright E2E yang relevan.

---

## 2. Keputusan dan Batasan yang Mengikat

AI agent **MUST** mengikuti keputusan berikut.

### 2.1 Pembagian PHASE 9

- **PHASE 9A:** hanya Beranda Publik.
- **PHASE 9B:** Admin Dashboard, dikerjakan setelah PHASE 9A selesai dan diverifikasi.
- Jangan menggabungkan implementasi Admin Dashboard ke dalam pekerjaan PHASE 9A, kecuali perubahan kecil yang mutlak diperlukan untuk menghindari regresi.
- Perubahan sidebar admin yang menghapus menu Beranda bukan fokus utama PHASE 9A. Jika diperlukan, catat sebagai pekerjaan navigasi terpisah dan jangan membangun modul CRUD Beranda.

### 2.2 Tidak ada modul CRUD Beranda baru

- Jangan membuat tabel, model, controller, route admin, atau halaman CRUD baru khusus Beranda hanya untuk mengatur section yang datanya sudah dikelola oleh modul lain.
- Tab **Beranda** di **Pengaturan Situs** tetap dipertahankan sebagai tempat mengatur opsi homepage yang memang sudah tersedia.
- Pengaturan global identitas dan kontak tetap berada di Pengaturan Situs.
- Berita berasal dari modul Berita, agenda dari modul Agenda, jadwal Misa dari modul Jadwal Misa, pelayanan dari modul Pelayanan, dan foto dari modul Galeri.
- Beranda bertindak sebagai halaman agregasi/presentasi; bukan sumber data kedua untuk konten yang sama.

### 2.3 Jangan menduplikasi sumber kebenaran

Gunakan layanan, shared props, model, query scopes, route names, dan komponen yang telah ada. Jangan membuat query atau helper paralel jika kapabilitas yang sama sudah tersedia.

Secara khusus:

- Gunakan `SiteSettingsService` untuk membaca konfigurasi situs.
- Gunakan shared props `identity`, `seo`, dan `contact` dari `HandleInertiaRequests` untuk data global yang sudah disediakan.
- Gunakan `NextMassResolver` untuk menentukan Misa berikutnya; jangan mengimplementasikan algoritma kedua di React atau controller.
- Gunakan scope/status publikasi yang sudah berlaku di setiap model/controller.
- Gunakan route names yang dihasilkan Laravel/Wayfinder dan pola navigasi proyek, bukan menulis URL secara acak bila helper route tersedia.

### 2.4 Tidak mengarang konten paroki

- Jangan menulis sejarah, slogan, motto/ayat, tanggal pendirian, nama pastor, berita, agenda, atau jadwal Misa seolah-olah merupakan data resmi jika belum tersedia dan diverifikasi.
- Teks yang berasal dari pengaturan kosong harus ditangani sebagai kondisi kosong, bukan diganti dengan klaim faktual buatan agent.
- Data contoh dari screenshot/mockup adalah **referensi visual**, bukan sumber kebenaran konten.
- Jangan menambahkan seeder konten produksi untuk membuat Beranda tampak penuh.

### 2.5 Teknologi dan arsitektur

Gunakan stack yang telah dipakai proyek:

- Laravel 13.
- Inertia.js 3.
- React 19 + TypeScript.
- Tailwind CSS 4 dan komponen UI yang telah tersedia.
- Vite Plus scripts yang telah didefinisikan dalam `package.json`.
- Pest untuk backend, Vitest untuk unit/component test frontend, dan Playwright untuk E2E.

Arsitektur tetap berupa Laravel layered architecture + React feature-oriented. Laravel adalah sumber kebenaran data, otorisasi, dan business logic. React bertanggung jawab atas presentasi dan interaksi.

---

## 3. Wajib Dibaca Sebelum Implementasi

Sebelum coding, agent wajib membaca:

1. `docs/PRD.md` — kebutuhan produk, fitur publik, SEO, aksesibilitas, aturan konten, dan keputusan terkait.
2. `docs/ARCHITECTURE.md` — alur Laravel → Inertia → React, struktur feature, query, dan aturan pemisahan tanggung jawab.
3. `docs/DESIGN.md` — design tokens, tipografi, spacing, komponen, warna, dan aturan visual.
4. `docs/DECISIONS.md` — terutama keputusan tentang timezone, Site Settings, media, publikasi, kontak, dan konsolidasi identitas.
5. `docs/roadmap/README.md` — status phase dan aturan roadmap.
6. File implementasi Beranda saat ini: `resources/js/features/public/beranda/pages/index.tsx`.
7. Route publik di `routes/web.php`.
8. `app/Http/Middleware/HandleInertiaRequests.php`.
9. `app/Services/SiteSettingsService.php`.
10. `app/Services/NextMassResolver.php` dan kelas data yang dikembalikannya.
11. Model, scope, controller, dan test untuk modul Mass Schedule, News, Agenda, Services, Gallery, dan Contact.
12. Komponen layout publik, navbar, footer, SEO, kartu, section, dan komponen media yang benar-benar ada di branch aktif.
13. Test yang saat ini memeriksa Beranda, navigasi publik, kontak, dan SEO.

> **Aturan audit:** path dalam dokumen ini adalah petunjuk awal berdasarkan audit. Jika nama atau lokasi file berbeda pada branch aktif, ikuti implementasi aktual, cari file yang setara, lalu catat perbedaannya. Jangan membuat file duplikat hanya karena nama yang disebut di sini tidak persis sama.

---

## 4. Kondisi Awal yang Diketahui

Audit awal repository menunjukkan:

- Route publik `/` saat ini merender halaman Inertia `public/beranda`.
- Halaman `resources/js/features/public/beranda/pages/index.tsx` masih merupakan placeholder fondasi dengan judul sambutan, teks `[ISI: ...]`, kartu statis, dan `HomeContactSection`.
- `HandleInertiaRequests` telah membagikan prop global `identity`, `seo`, dan `contact`.
- `SiteSettingsService` menyediakan akses terpusat ke setting yang terdaftar di `config/site-settings.php`.
- `NextMassResolver` telah tersedia dan memperhitungkan zona waktu tampilan `Asia/Pontianak`.
- Modul jadwal Misa, berita, agenda, pelayanan, komunitas, area, galeri, dokumen, dan kontak telah dibangun dalam phase sebelumnya.
- Konten produksi belum tentu terisi. Status engineering complete tidak berarti data resmi paroki telah disediakan.

Temuan ini harus diverifikasi ulang pada branch dan commit yang benar-benar dikerjakan. Jangan menganggap kondisi test, CI, atau isi file tetap sama tanpa pemeriksaan.

---

## 5. Inventaris Pengaturan Situs dan Sumber Data

### 5.1 Setting yang sudah terdaftar

`config/site-settings.php` adalah sumber daftar key, tipe, default, dan grup Pengaturan Situs. Audit awal menunjukkan grup berikut:

| Grup | Key yang relevan | Penggunaan potensial pada Beranda |
| --- | --- | --- |
| Identitas | `parish_name`, `parish_short_name`, `tagline`, `short_description`, `motto_verse`, `patron_saint`, `logo`, `logo_alt`, `favicon` | Identitas header/footer, eyebrow hero, sambutan singkat, teks alternatif logo. |
| Kontak | `contact_address`, `contact_phone`, `contact_whatsapp`, `contact_email`, `office_hours`, `maps_link` | Bagian kontak; gunakan shared prop `contact` agar URL aman dan data terformat. |
| Profil | `description`, `established_year`, `cover`, `cover_alt`, `video_url`, `is_published` | Hanya gunakan jika semantik dan aturan publikasinya sudah dipahami. Jangan otomatis menganggap `cover` adalah hero image. |
| Sosial | `social_facebook`, `social_instagram`, `social_youtube`, `social_whatsapp_channel`, `website` | Link sosial pada footer atau bagian yang sesuai. |
| SEO | `seo_default_title`, `seo_default_description`, `seo_default_og_image` | Metadata halaman, melalui kontrak SEO yang sudah ada. |
| Beranda | `home_news_limit`, `home_events_limit`, `home_gallery_limit`, `home_show_devotion`, `home_show_gallery`, `home_show_services`, `home_show_contact` | Batas jumlah konten dan visibilitas section yang didukung konfigurasi. |
| Privasi | `privacy_policy_content` | Bukan bagian inti Beranda; jangan tampilkan di homepage. |

### 5.2 Setting yang belum otomatis berarti siap digunakan

Audit awal menemukan beberapa setting belum tentu memiliki consumer publik yang lengkap. Sebelum implementasi, agent wajib memeriksa:

- Apakah `motto_verse` dan `patron_saint` sudah menjadi bagian dari desain hero atau hanya metadata identitas?
- Apakah `description`, `established_year`, `cover`, `cover_alt`, `video_url`, dan `is_published` memiliki aturan publikasi dan penggunaan yang jelas?
- Apakah `home_show_devotion` memiliki sumber konten renungan yang benar-benar tersedia? Jika tidak ada modul/data renungan, jangan membuat renungan palsu. Jangan mengaktifkan section kosong.
- Apakah tersedia field untuk mengatur hero title, hero description, hero image, label CTA, dan target CTA? Jangan mengasumsikan setting yang tidak tercantum memang sudah ada.
- Apakah media hero dapat menggunakan file gambar yang diberikan pengguna melalui pipeline Media saat ini? Verifikasi format, ukuran, penyimpanan, URL, dan batas upload.

Jika setting yang dibutuhkan belum tersedia, buat rekomendasi paling kecil yang tetap sesuai arsitektur. Jangan menambahkan semua field yang mungkin berguna. Field baru hanya boleh ditambahkan bila diperlukan untuk acceptance criteria yang telah disetujui dan tidak bisa dipenuhi dengan setting yang ada.

### 5.3 Kontrak sumber data section

Gunakan tabel ini sebagai target pemetaan; agent harus memverifikasi nama relasi, status, scope, dan bentuk props dari kode aktual.

| Section | Sumber data yang diharapkan | Aturan |
| --- | --- | --- |
| Navbar | Shared `identity`; route publik yang sudah ada | Gunakan navbar/layout publik yang ada. Jangan membuat navbar kedua khusus homepage. |
| Hero | Shared `identity` + setting/field hero yang telah diverifikasi + aset hero yang disetujui | Tidak boleh menggunakan teks placeholder atau klaim paroki rekaan. Jika belum ada field yang memadai, dokumentasikan gap sebelum menambahkannya. |
| Quick links | Route publik yang sudah tersedia | Akses cepat ke Jadwal Misa, Berita, Agenda, dan Pelayanan. |
| Misa berikutnya | `NextMassResolver` dan struktur `NextMass` | Waktu ditampilkan dalam `Asia/Pontianak`; state kosong ditangani dengan baik. |
| Berita terbaru | Model/query publik berita | Hanya konten yang memang boleh tampil publik; eager load media/kategori yang diperlukan. |
| Agenda mendatang | Model/query publik agenda | Terapkan status dan waktu yang benar; jangan mengubah agenda batal/ditunda menjadi agenda normal. |
| Pelayanan Sakramen | Modul Pelayanan publik | Hanya pelayanan yang aktif/terbit sesuai aturan modul; gunakan limit/urutan yang sudah tersedia bila ada. |
| Galeri Kegiatan | Modul Galeri publik | Hanya album/foto yang dipublikasikan; patuhi `home_show_gallery` dan `home_gallery_limit`. |
| Kontak | Shared `contact` | Patuh pada `contact.homeShowContact`; jangan membaca ulang tabel setting di React. |
| Footer | Komponen footer yang ada + shared props | Jangan menggandakan logika identitas, kontak, atau sosial media. |
| SEO | Shared `seo` dan mekanisme SEO yang ada | Title/description harus sesuai halaman; OG image hanya jika URL yang valid tersedia. |

**Penting:** daftar ini tidak memerintahkan pembuatan controller atau service untuk setiap section. Pilih pola paling sederhana yang cocok dengan kode aktual.

---

## 6. Target UI/UX dan Hierarki Visual

### 6.1 Referensi visual yang disetujui

Gunakan mockup homepage terakhir yang disetujui pengguna sebagai acuan visual. Gambar hero yang diberikan pengguna menampilkan gereja bercahaya pada malam hari dengan patung Bunda Maria di sisi kanan. Gambar tersebut harus menjadi aset hero yang digunakan jika tersedia di repository dan sudah melalui pipeline media yang sesuai.

Mockup adalah acuan layout, spacing, urutan section, proporsi, dan hierarki. Mockup **bukan** sumber data untuk jadwal Misa, berita, tanggal, nama kegiatan, kontak, atau isi resmi lainnya.

Jika aset gambar hanya tersedia sebagai attachment percakapan dan belum berada di repository, jangan menebak path atau membuat referensi file yang tidak ada. Laporkan bahwa aset perlu ditempatkan di lokasi aset/media proyek dan ikuti pipeline yang tersedia. Jangan menambahkan file besar secara otomatis tanpa memeriksa aturan media dan cara asset dikelola.

### 6.2 Prinsip desain

- Bersih, lapang, tenang, dan mudah dipindai.
- Gunakan hierarki yang konsisten: satu H1 utama; H2 untuk section; heading kartu lebih kecil.
- Gunakan warna utama merah paroki sesuai `DESIGN.md`; gunakan warna netral untuk latar dan teks sekunder.
- Hindari terlalu banyak border, shadow, badge, icon, dan variasi card.
- Jangan membuat semua section tampak seperti grid kartu yang sama.
- Gunakan whitespace dan pembatas section untuk membentuk ritme halaman.
- Hindari layout yang padat dan teks terlalu kecil.
- Pastikan kontras, fokus keyboard, hover/focus states, dan ukuran target interaksi memadai.
- Gunakan icon library dan komponen yang sudah tersedia. Jangan menambah dependensi UI tanpa kebutuhan kuat.
- Hormati tema terang/gelap jika fitur tema sudah menjadi bagian dari shell publik.

### 6.3 Urutan section yang ditargetkan

Urutan default berikut mengikuti mockup yang disetujui dan harus dikonfirmasi terhadap PRD/DESIGN aktual:

1. **Header / Navbar** — identitas paroki, navigasi utama, kontrol tema bila tersedia, CTA Jadwal Misa.
2. **Hero** — foto gereja malam sebagai visual utama; teks sambutan yang bersumber dari setting terverifikasi; CTA utama ke Jadwal Misa dan CTA sekunder ke Profil/Sejarah.
3. **Quick links** — empat pintasan yang jelas untuk Jadwal Misa, Berita, Agenda, dan Pelayanan.
4. **Misa Berikutnya** — section dengan prioritas visual tinggi, waktu, nama Misa, lokasi, dan link ke seluruh jadwal.
5. **Berita Terbaru** — kartu editorial dengan gambar jika tersedia, tanggal, judul, ringkasan, dan link detail.
6. **Agenda Mendatang** — daftar ringkas dengan tanggal dan informasi penting; link ke halaman Agenda.
7. **Pelayanan Sakramen** — pilihan pelayanan yang tersedia dan dipublikasikan; link ke detail/daftar pelayanan.
8. **Galeri Kegiatan** — foto terpilih dari album yang dipublikasikan; link ke Galeri.
9. **Kontak** — alamat dan kanal komunikasi yang tersedia, jam layanan, dan peta jika valid; mengikuti `home_show_contact`.
10. **Footer** — identitas, tautan cepat, sosial media yang tersedia, serta tautan kebijakan yang memang ada.

Aturan kondisional:

- `home_show_services = false` menyembunyikan section Pelayanan.
- `home_show_gallery = false` menyembunyikan section Galeri.
- `home_show_contact = false` menyembunyikan blok kontak Beranda, tetapi **tidak** menghapus atau menonaktifkan halaman `/kontak`.
- `home_show_devotion` hanya boleh mengontrol section renungan jika sumber data dan perilaku section tersebut telah diverifikasi. Jika tidak ada sumber data renungan, jangan membangun section renungan dalam PHASE 9A hanya untuk memenuhi flag.
- `home_news_limit`, `home_events_limit`, dan `home_gallery_limit` digunakan sebagai batas jumlah konten sesuai validasi config. Jangan hard-code angka yang mengabaikan setting.

### 6.4 Hero image

- Gunakan gambar gereja malam yang disetujui sebagai gambar hero utama setelah aset tersedia pada lokasi yang benar.
- Pastikan foto tidak menutupi teks utama atau CTA.
- Pilih komposisi/crop yang tetap mempertahankan gereja dan patung Bunda Maria sebagai fokus.
- Terapkan overlay/gradient hanya bila diperlukan untuk keterbacaan; jangan membuat gambar terlalu gelap atau mengubah karakter aslinya.
- Sediakan alt text yang menjelaskan gambar secara tepat. Jika deskripsi gambar tidak disimpan dalam setting, gunakan mekanisme alt yang sesuai dengan pola proyek dan dokumentasikan keputusan.
- Jangan mengunduh atau mengambil gambar lain dari internet untuk mengganti gambar yang disetujui.
- Gunakan pipeline media/asset yang telah ada. Jangan mengasumsikan media setting `cover` otomatis merupakan hero image.

### 6.5 Responsive behavior

Desktop:

- Navbar tidak saling bertumpuk; logo dan nama paroki memiliki ruang cukup.
- Hero menyeimbangkan teks dan foto tanpa mempersempit area baca.
- Section menggunakan container dan grid yang konsisten.
- Misa berikutnya memiliki penekanan visual tetapi tidak mendominasi seluruh halaman.

Tablet:

- Grid berkurang kolom secara wajar.
- Kartu berita dan pelayanan tetap terbaca.
- Navigasi menggunakan perilaku responsive yang sudah ada pada shell.

Mobile:

- Tidak ada horizontal overflow.
- Navbar menggunakan menu mobile yang aksesibel.
- Hero memiliki urutan teks/visual yang jelas; CTA dapat ditap dengan mudah.
- Quick links dan kartu berubah menjadi satu kolom atau grid yang sesuai.
- Tanggal, jam, judul, dan lokasi tidak terpotong.
- Kontak dan map tidak memaksa lebar viewport.
- Tidak mengandalkan hover untuk mengakses informasi.

Target verifikasi minimal: lebar viewport 360 px, 390 px, 768 px, 1024 px, dan desktop sekitar 1440 px.

---

## 7. Rencana Arsitektur dan Implementasi

### 7.1 Route dan page entry point

Route publik `/` tetap menggunakan route name `home` dan merender halaman Beranda publik melalui Inertia. Jangan membuat route homepage kedua atau API terpisah untuk data yang dapat disediakan langsung melalui Inertia.

Saat audit, periksa apakah `Route::inertia('/', 'public/beranda')` masih cukup atau perlu diganti dengan controller yang mengirim data dinamis. Jika data homepage memerlukan beberapa query dan pemetaan data, gunakan controller publik tipis atau pola yang sudah dipakai modul lain. Pilih satu pendekatan berdasarkan konvensi proyek, bukan menambahkan controller hanya untuk mengikuti dokumen ini.

### 7.2 Bentuk props

- Semua data yang dibutuhkan halaman dikirim dari Laravel melalui Inertia props.
- Definisikan tipe TypeScript eksplisit untuk props Beranda; hindari `any`.
- Jangan mengirim raw Eloquent model beserta kolom internalnya ke browser.
- Kirim hanya field yang benar-benar dirender.
- Serialisasi tanggal/waktu mengikuti pola proyek dan gunakan ISO 8601 bila mengirim datetime.
- Jangan menyalin server props ke Zustand atau global client state. Gunakan props Inertia sebagai sumber data server.
- Shared props global tetap ringkas; data khusus homepage sebaiknya berupa page props, kecuali memang dibutuhkan semua halaman.

Sebelum menetapkan nama interface/shape baru, audit `resources/js/types/` dan pola props yang digunakan controller publik lain.

### 7.3 Query dan performa

- Gunakan Eloquent scopes/status publikasi yang telah ada.
- Gunakan eager loading untuk relasi yang dipakai dalam tampilan.
- Pilih hanya kolom yang dibutuhkan jika konsisten dengan pola proyek.
- Batasi jumlah item dari setting yang sesuai.
- Jangan menjalankan query yang sama di beberapa section bila hasilnya dapat dipakai ulang secara aman.
- Jangan memuat seluruh berita, agenda, album, atau foto hanya untuk memotong array di React.
- Hindari N+1 queries.
- Jangan menambah cache homepage baru sebelum memeriksa cache setting yang sudah ada dan mengukur kebutuhan. Jika cache ditambahkan, dokumentasikan invalidasi saat konten diterbitkan/diperbarui.
- Penanganan error dan empty state harus tetap menghasilkan halaman valid.

### 7.4 Misa berikutnya

Gunakan `App\Services\NextMassResolver` yang sudah ada.

- Jangan menghitung hari/jam Misa dengan JavaScript sebagai sumber kebenaran.
- Jangan menulis algoritma kedua di controller.
- Pahami bentuk hasil `App\Support\NextMass` sebelum mendesain prop.
- Tampilkan tanggal dan jam dalam zona waktu tampilan `Asia/Pontianak`.
- Jadwal Misa rutin menggunakan jam dinding lokal; Misa khusus menggunakan datetime yang tersimpan UTC dan dikonversi untuk tampilan.
- Ketika resolver mengembalikan `null`, tampilkan empty state yang informatif dan tautan ke halaman Jadwal Misa. Jangan membuat jadwal contoh.
- Uji batas waktu: Misa tepat pada waktu saat ini, Misa yang sudah lewat hari ini, perpindahan hari/minggu, Misa khusus, serta tidak adanya jadwal berikutnya.
- Pertahankan aturan resolver bahwa Misa khusus menang jika terjadi tie sesuai implementasi saat ini.

### 7.5 Berita terbaru

- Ambil hanya berita yang boleh diakses publik berdasarkan aturan modul yang ada.
- Urutkan berdasarkan aturan publikasi modul, bukan sekadar `created_at` jika ada `published_at` atau aturan lain.
- Gunakan limit dari `home_news_limit`.
- Tampilkan tanggal publikasi jika tersedia, judul, excerpt/ringkasan, gambar utama jika tersedia, dan link detail.
- Jika gambar tidak tersedia, gunakan fallback visual yang telah disetujui dalam design system; jangan membuat URL gambar palsu.
- Jangan menampilkan draft, scheduled post yang belum waktunya, archived, soft-deleted, atau konten yang tidak boleh publik.
- Empty state tidak boleh menampilkan kartu dummy.

### 7.6 Agenda mendatang

- Gunakan query/scope publik Agenda yang sudah tersedia.
- Terapkan batas `home_events_limit`.
- Tanggal harus dihitung dan diformat dengan zona waktu tampilan yang sesuai.
- Ikuti perilaku status `cancelled` dan `postponed` yang ditentukan keputusan proyek. Jika event tersebut masih boleh muncul pada daftar mendatang menurut modul, tampilkan label status yang benar; jangan menyamarkannya sebagai event normal.
- Jangan menampilkan agenda yang sudah lewat sebagai agenda mendatang.
- Tampilkan tanggal, waktu bila tersedia, nama agenda, lokasi bila tersedia, dan tautan detail.
- Jika tidak ada agenda, tampilkan empty state yang tenang dan tidak merusak hierarki layout.

### 7.7 Pelayanan

- Gunakan modul Pelayanan yang sudah tersedia.
- Verifikasi nama field, scope, urutan, relasi kategori, dan route detail aktual.
- Hanya tampilkan pelayanan yang aktif/terbit sesuai aturan modul.
- Jangan hard-code enam sakramen sebagai data UI jika data sebenarnya tersedia dari database.
- Jika ada kategori/jenis yang wajib ditampilkan tetapi belum ada data publik, jangan mengarang deskripsi atau persyaratan.
- Section dapat disembunyikan melalui `home_show_services` bila setting tersebut sudah bekerja.

### 7.8 Galeri

- Gunakan album/foto dari modul Galeri.
- Hormati status publikasi album/foto dan mekanisme sampul yang sudah berlaku.
- Gunakan `home_gallery_limit` untuk membatasi jumlah item sesuai semantik yang ada (album atau foto); audit dahulu sebelum menetapkan.
- Hormati `home_show_gallery`.
- Gunakan URL media/variant yang dihasilkan oleh pipeline media. Jangan membangun URL storage secara manual bila model/helper sudah menyediakannya.
- Sediakan alt text yang sesuai; jika alt tidak tersedia, gunakan fallback yang tidak mengarang informasi.
- Jangan menampilkan dokumen privat sebagai gambar galeri.

### 7.9 Kontak

- Gunakan shared prop `contact` dari `HandleInertiaRequests`.
- Gunakan `contact.homeShowContact` untuk menentukan apakah blok kontak homepage dirender.
- Reuse `HomeContactSection` yang sudah ada jika memenuhi desain; perbaiki/pecah komponennya bila perlu, tetapi hindari implementasi kontak kedua dengan logika yang berbeda.
- Nomor telepon, WhatsApp, email, jam layanan, URL arah, dan URL embed map harus memakai hasil yang sudah diproses di backend.
- Sembunyikan item yang kosong atau tidak valid. Jangan menghasilkan tautan `tel:`, `mailto:`, WhatsApp, atau map yang rusak.
- Jangan menganggap alamat pada screenshot lama sebagai data resmi paroki. Gunakan setting yang telah diverifikasi.
- Jika map tidak tersedia, layout harus tetap rapi tanpa iframe kosong.

### 7.10 SEO dan metadata

- Gunakan mekanisme SEO/Head yang telah ada.
- Gunakan `seo` shared prop sebagai fallback, lalu metadata spesifik homepage yang sesuai.
- Title harus deskriptif dan tidak tetap “Selamat Datang”.
- Description harus berasal dari setting yang valid atau teks yang telah disetujui; jangan membuat klaim faktual.
- Gunakan Open Graph image hanya jika tersedia URL yang valid dan dapat diakses.
- Periksa canonical/robots/schema hanya sesuai implementasi dan scope PRD; jangan menambahkan structured data yang belum disepakati.
- Pastikan heading hierarchy semantik: satu H1 utama, H2 untuk section, heading kartu pada level yang sesuai.

---

## 8. Daftar Pekerjaan (TODO) Berurutan

Agent wajib menyelesaikan setiap kelompok pekerjaan sesuai dependency. Jangan mulai kelompok implementasi sebelum audit dan keputusan yang diperlukan selesai.

### PH9A-01 — Audit repository dan baseline

- [ ] Pastikan branch dan commit target.
- [ ] Baca semua dokumen wajib di Bagian 3.
- [ ] Audit route `/`, komponen Beranda, layout publik, navbar, footer, SEO, shared props, dan design tokens.
- [ ] Audit `config/site-settings.php`, form Pengaturan Situs, request validation, dan `SiteSettingsService`.
- [ ] Audit `NextMassResolver`, `NextMass`, model MassSchedule/SpecialMass, scope dan test.
- [ ] Audit model, scope, controller, route, bentuk media, dan test untuk Berita, Agenda, Pelayanan, dan Galeri.
- [ ] Audit E2E yang mengandalkan title atau placeholder lama agar dapat diperbarui tanpa mengurangi coverage.
- [ ] Jalankan baseline test/check yang relevan atau catat kegagalan yang sudah ada sebelum perubahan.
- [ ] Catat temuan, file yang akan disentuh, gap requirement, dan risiko.

**Output:** daftar audit singkat dalam laporan kerja/plan implementasi. Jangan coding sebelum gap yang memengaruhi struktur data dan aset hero diputuskan.

### PH9A-02 — Konfirmasi kontrak data

- [ ] Buat pemetaan section → setting/model/query/scope/route.
- [ ] Konfirmasi field hero yang tersedia dan field mana yang belum ada.
- [ ] Konfirmasi lokasi dan cara mengelola gambar hero yang disetujui.
- [ ] Konfirmasi semantik `home_gallery_limit` (jumlah album atau foto).
- [ ] Konfirmasi ketersediaan sumber renungan untuk `home_show_devotion`.
- [ ] Konfirmasi empty state untuk setiap section.
- [ ] Tentukan tipe props TypeScript dan bentuk data minimal yang aman.
- [ ] Jika ada perubahan schema/setting yang benar-benar dibutuhkan, dokumentasikan alasannya dan tunggu persetujuan sesuai workflow proyek sebelum menambahkannya.

**Gate:** jangan mulai implementasi UI final sampai kontrak data, aset hero, dan gap konfigurasi diketahui.

### PH9A-03 — Struktur halaman dan hero

- [ ] Ganti placeholder pada `resources/js/features/public/beranda/pages/index.tsx` sesuai arsitektur aktual.
- [ ] Reuse PublicLayout/navbar/footer dan komponen umum yang sudah tersedia.
- [ ] Implementasikan hero yang mengikuti mockup yang disetujui.
- [ ] Gunakan gambar hero gereja malam yang diberikan pengguna jika sudah tersedia melalui asset/media pipeline.
- [ ] Pastikan teks hero berasal dari sumber yang telah dikonfirmasi.
- [ ] Implementasikan CTA utama menuju Jadwal Misa dan CTA sekunder menuju halaman Profil/Sejarah yang benar-benar tersedia.
- [ ] Implementasikan quick links ke Jadwal Misa, Berita, Agenda, dan Pelayanan.
- [ ] Hapus teks `[ISI: ...]`, tombol Dashboard/Masuk yang tidak relevan bagi pengunjung publik, serta catatan placeholder fondasi dari Beranda publik.
- [ ] Pastikan header dan hero tetap terbaca pada desktop dan mobile.

### PH9A-04 — Data dan section dinamis

- [ ] Implementasikan integrasi Misa berikutnya menggunakan `NextMassResolver`.
- [ ] Implementasikan Berita Terbaru dari data publik dan limit setting.
- [ ] Implementasikan Agenda Mendatang dari data publik dan limit setting.
- [ ] Implementasikan Pelayanan dari modul publik dan aturan publikasinya.
- [ ] Implementasikan Galeri dari album/foto publik dan limit setting.
- [ ] Pertahankan atau refactor `HomeContactSection` untuk memakai shared `contact`.
- [ ] Terapkan toggle section dari Pengaturan Situs yang relevan.
- [ ] Implementasikan empty state untuk setiap section tanpa data.
- [ ] Pastikan tidak ada konten dummy, placeholder, URL palsu, atau data dari screenshot yang dipakai sebagai data produksi.
- [ ] Pastikan tidak ada relasi/tabel duplikat yang dibuat khusus homepage.

### PH9A-05 — Responsive design dan accessibility

- [ ] Verifikasi layout di viewport 360, 390, 768, 1024, dan 1440 px.
- [ ] Perbaiki overflow horizontal, grid, ukuran heading, jarak antarsection, dan wrapping teks.
- [ ] Pastikan navbar mobile bisa digunakan dengan keyboard dan screen reader sesuai pola shell.
- [ ] Pastikan semua tombol/link punya nama yang jelas.
- [ ] Pastikan gambar punya alt text yang sesuai dan gambar dekoratif menggunakan alt kosong bila tepat.
- [ ] Pastikan urutan heading semantik.
- [ ] Pastikan fokus keyboard terlihat dan kontras memadai sesuai design system.
- [ ] Pastikan layout tetap usable ketika gambar hero atau gambar konten tidak tersedia.
- [ ] Hindari animasi yang mengganggu; hormati reduced-motion bila animasi digunakan.

### PH9A-06 — SEO, performance, dan hardening

- [ ] Integrasikan metadata homepage dengan mekanisme SEO yang ada.
- [ ] Pastikan query terbatas, eager loading diterapkan, dan tidak ada N+1 yang jelas.
- [ ] Pastikan hanya field yang dibutuhkan dikirim ke browser.
- [ ] Pastikan gambar hero dan kartu tidak menyebabkan layout shift berlebihan.
- [ ] Gunakan ukuran/variant gambar yang sesuai dengan pipeline yang tersedia.
- [ ] Jangan menambahkan library atau cache baru tanpa alasan terukur.
- [ ] Pastikan semua tautan menggunakan route yang valid.
- [ ] Pastikan tidak ada exception ketika setting null, media hilang, atau tabel konten kosong.

### PH9A-07 — Test dan regresi

- [ ] Tambahkan/ubah Pest Feature tests untuk homepage dan kontrak data server.
- [ ] Tambahkan/ubah Vitest tests untuk komponen/empty state/conditional sections bila logika komponen cukup kompleks.
- [ ] Perbarui Playwright E2E untuk Beranda, navigasi, CTA, dan responsive-critical behavior.
- [ ] Jangan sekadar menghapus assertion placeholder lama; ganti dengan assertion yang memverifikasi perilaku baru.
- [ ] Jalankan `composer ci:check`.
- [ ] Jalankan `npm run build`.
- [ ] Jalankan `npm run e2e` sesuai environment proyek.
- [ ] Verifikasi seluruh workflow CI terbaru pada commit/PR.
- [ ] Catat kegagalan yang memang sudah ada sebelumnya secara terpisah; jangan menyebut phase selesai jika tes yang diwajibkan gagal.

### PH9A-08 — Dokumentasi dan handoff

- [ ] Perbarui status roadmap hanya setelah acceptance criteria dan test gate terpenuhi.
- [ ] Catat keputusan baru di `docs/DECISIONS.md` jika ada perubahan kontrak atau keputusan arsitektur.
- [ ] Catat field Pengaturan Situs yang digunakan oleh Beranda dan keterbatasan yang tersisa.
- [ ] Catat jika aset hero belum masuk repository atau memerlukan langkah operasional.
- [ ] Dokumentasikan test yang dijalankan dan hasilnya.
- [ ] Pastikan pekerjaan PHASE 9B tetap terpisah.

---

## 9. Acceptance Criteria

PHASE 9A baru boleh dinyatakan selesai bila semua kriteria wajib berikut terpenuhi.

### AC-01 — Beranda publik

- [ ] `/` menampilkan homepage final, bukan placeholder fondasi.
- [ ] Tidak ada teks `[ISI: ...]`, teks “Selamat Datang” placeholder, atau pesan bahwa homepage akan dibangun pada phase berikutnya.
- [ ] Halaman tetap dapat diakses tanpa login.
- [ ] Layout konsisten dengan design system dan mockup yang disetujui.

### AC-02 — Hero dan navigasi

- [ ] Identitas publik memakai shared `identity` atau sumber yang telah disetujui.
- [ ] Hero memakai aset gereja malam yang disetujui jika aset tersedia di proyek.
- [ ] Hero tidak menampilkan klaim, motto, data, atau teks yang belum diverifikasi.
- [ ] CTA utama mengarah ke route Jadwal Misa yang valid.
- [ ] CTA sekunder mengarah ke halaman Profil/Sejarah yang valid pada route aktual.
- [ ] Quick links mengarah ke halaman publik yang benar.

### AC-03 — Data dinamis

- [ ] Misa berikutnya berasal dari `NextMassResolver`.
- [ ] Berita terbaru hanya menampilkan data yang boleh dipublikasikan.
- [ ] Agenda mendatang mengikuti status dan waktu yang benar.
- [ ] Pelayanan hanya menampilkan data yang sesuai aturan publikasi.
- [ ] Galeri hanya menampilkan media yang boleh dipublikasikan.
- [ ] Kontak menggunakan shared `contact`.
- [ ] Tidak ada data dummy permanen yang menyamar sebagai konten paroki.

### AC-04 — Pengaturan Situs

- [ ] Limit berita, agenda, dan galeri mengikuti setting yang telah tersedia.
- [ ] Toggle section yang didukung berfungsi sesuai makna yang didokumentasikan.
- [ ] `home_show_contact = false` hanya menyembunyikan blok kontak Beranda; halaman `/kontak` tetap berfungsi.
- [ ] Jika sebuah setting belum memiliki sumber data/consumer yang valid, agent tidak membuat konten palsu hanya agar toggle terlihat berfungsi.

### AC-05 — Empty states dan robustness

- [ ] Homepage tetap valid saat belum ada Misa berikutnya.
- [ ] Homepage tetap valid saat belum ada berita, agenda, pelayanan, album, atau foto.
- [ ] Setting identitas/kontak yang kosong tidak menyebabkan tautan rusak atau layout pecah.
- [ ] Gambar yang hilang atau URL yang tidak valid ditangani secara aman.
- [ ] Section opsional yang tidak memiliki data tidak meninggalkan ruang kosong besar atau judul tanpa isi.

### AC-06 — Responsive dan accessibility

- [ ] Tidak ada horizontal overflow pada viewport 360 px dan ukuran lain yang diuji.
- [ ] Navigasi, CTA, kartu, dan link dapat digunakan dengan keyboard.
- [ ] Heading hierarchy dan alt text sesuai.
- [ ] Fokus terlihat dan kontras memenuhi aturan design system.
- [ ] Konten tetap terbaca tanpa mengandalkan hover.

### AC-07 — Kualitas teknis

- [ ] Tidak ada raw Eloquent model yang dikirim ke frontend.
- [ ] Tidak ada query per kartu atau N+1 yang belum ditangani.
- [ ] Tidak ada route/URL palsu.
- [ ] TypeScript tidak menggunakan `any` baru untuk menghindari kontrak props.
- [ ] `composer ci:check` lulus.
- [ ] `npm run build` lulus.
- [ ] E2E yang relevan lulus.
- [ ] CI pada commit final lulus.

### AC-08 — Batas scope

- [ ] Tidak ada modul CRUD Beranda baru.
- [ ] Tidak ada tabel baru untuk menduplikasi berita, agenda, jadwal, pelayanan, galeri, identitas, atau kontak.
- [ ] Tidak ada implementasi Admin Dashboard sebagai bagian PHASE 9A.
- [ ] Tidak ada refactor tak terkait yang memperbesar risiko regresi.

---

## 10. Strategi Testing

### 10.1 Backend — Pest

Buat atau perbarui test sesuai pola yang sudah ada. Cakupan minimum:

1. Guest dapat membuka route homepage.
2. Response homepage memuat props yang sesuai dan tidak membocorkan field internal.
3. Berita draft/terarsip/tidak terbit tidak muncul.
4. Agenda yang tidak memenuhi aturan publikasi tidak muncul.
5. Limit berita dan agenda dihormati.
6. Resolver Misa berikutnya digunakan dan hasilnya diformat dengan benar untuk zona waktu tampilan.
7. Setting toggle menyembunyikan section yang sesuai.
8. `home_show_contact` tidak memengaruhi route `/kontak`.
9. Kondisi data kosong tidak menyebabkan exception.
10. Relasi media di-load secara efisien dan tidak menimbulkan query N+1 yang jelas.

Jangan mengunci test pada nama field yang belum dikonfirmasi. Cocokkan dengan model, enum, scope, dan pola test aktual.

### 10.2 Frontend — Vitest

Tambahkan test hanya pada perilaku yang layak diuji di level komponen, misalnya:

- Render empty state.
- Conditional rendering untuk section opsional.
- Link dan label CTA yang benar.
- Format/presentasi data dari props.
- Komponen kartu tanpa gambar.
- Aksesibilitas dasar untuk kontrol interaktif.

Jangan membuat test snapshot besar yang rapuh sebagai pengganti test perilaku.

### 10.3 End-to-end — Playwright

Cakupan minimum:

- Pengunjung membuka `/` tanpa login.
- Hero dan section utama tampil.
- CTA Jadwal Misa membuka route yang benar.
- Link Berita, Agenda, Pelayanan, Galeri, dan Kontak berfungsi.
- Tidak ada placeholder lama.
- Beranda dapat digunakan pada viewport mobile.
- Halaman tetap usable saat data beberapa section kosong.
- Navigasi publik yang sudah ada tidak mengalami regresi.

Hindari test yang bergantung pada isi berita atau agenda tertentu jika data itu tidak dijamin oleh fixture/factory E2E. Gunakan fixture yang deterministik atau assertion yang sesuai dengan data yang memang dikontrol test.

---

## 11. Perintah Verifikasi

Sebelum menjalankan perintah, baca `composer.json`, `package.json`, dan workflow CI aktif. Daftar berikut mencerminkan script yang ditemukan pada audit awal dan harus diverifikasi ulang.

```bash
# Backend + frontend checks + unit tests + Pest, sesuai composer script
composer ci:check

# Build production
npm run build

# Playwright E2E (mempersiapkan environment E2E terlebih dahulu)
npm run e2e
```

Jika ingin menjalankan test lebih terarah saat development, gunakan script atau command yang sudah ada di repository. Jangan mengarang script baru hanya untuk mengikuti dokumen ini.

Setelah implementasi:

- Jalankan test relevan selama pengerjaan.
- Jalankan keseluruhan gate sebelum membuat PR/menyatakan selesai.
- Periksa status workflow GitHub untuk commit/PR yang sedang dikerjakan.
- Laporkan jumlah test lulus/gagal dan command yang benar-benar dijalankan. Jangan mengklaim test lulus jika tidak dijalankan.

---

## 12. Definition of Done (DoD)

PHASE 9A selesai jika:

1. Semua Acceptance Criteria wajib terpenuhi.
2. Placeholder homepage telah diganti dengan Beranda publik final.
3. Hero, data dinamis, conditional sections, empty states, SEO, dan responsive behavior berfungsi.
4. Tidak ada modul/tabel/CRUD Beranda duplikat.
5. Tidak ada data paroki yang dikarang atau konten dummy permanen.
6. Pest, Vitest, type-check/check, build, dan E2E relevan lulus sesuai gate proyek.
7. CI final lulus.
8. Dokumentasi roadmap dan keputusan yang diperlukan diperbarui.
9. Hasil implementasi, file penting, test, dan gap yang tersisa dirangkum untuk reviewer.
10. PHASE 9B belum diimplementasikan sebagai bagian dari PR PHASE 9A.

---

## 13. Di Luar Scope PHASE 9A

Jangan mengerjakan hal berikut kecuali ada persetujuan eksplisit:

- Admin Dashboard: statistik, aktivitas terbaru, alert admin, dan shortcut administrasi (PHASE 9B).
- Modul CRUD Beranda baru atau pengelola section generik.
- CMS hero/banner baru sebelum audit membuktikan kebutuhan dan disetujui.
- Sistem renungan baru jika sumber data belum tersedia.
- Penambahan relasi komunitas langsung pada berita atau agenda.
- Rebuild navbar, footer, design system, atau seluruh modul publik tanpa kebutuhan.
- SEO/performance/security audit menyeluruh yang merupakan PHASE 10, selain perbaikan yang diperlukan untuk acceptance criteria homepage.
- Pengisian konten produksi oleh agent.
- Migrasi besar atau refactor tidak terkait.
- Penambahan library UI/animation tanpa kebutuhan yang jelas.

---

## 14. Instruksi Operasional untuk AI Agent

Saat menerima tugas PHASE 9A, ikuti urutan ini:

1. **Audit terlebih dahulu.** Baca dokumen wajib, cek branch/commit, inspeksi route, setting, model, scope, shared props, komponen, test, dan CI.
2. **Tulis rencana implementasi sebelum coding.** Petakan setiap section ke sumber data dan file yang akan diubah. Catat dependency, gap setting, kebutuhan aset, dan risiko.
3. **Jangan menebak.** Jika sumber data, semantik setting, route, atau field hero belum jelas, cari implementasi aktual. Jika tetap tidak jelas, laporkan pertanyaan/keputusan yang diperlukan sebelum membuat perubahan schema.
4. **Implementasikan secara bertahap.** Mulai dari kontrak props dan hero/layout, kemudian section dinamis, responsive/accessibility, SEO, dan test.
5. **Ikuti pola yang sudah ada.** Reuse controller/service/model/query scopes/components dan route names. Hindari abstraksi baru yang tidak diperlukan.
6. **Jaga scope.** Jangan mengimplementasikan PHASE 9B atau membuat CRUD Beranda.
7. **Verifikasi.** Jalankan test dan build yang benar-benar tersedia; cek CI; perbaiki regresi yang diperkenalkan oleh perubahan ini.
8. **Laporkan hasil.** Berikan ringkasan implementasi, daftar file yang diubah, keputusan, hasil test, status CI, dan pekerjaan yang belum selesai.

### Format laporan akhir agent

```text
PHASE 9A — Implementation Report

Status: DONE | BLOCKED | PARTIAL

Summary:
- ...

Data contracts:
- Hero:
- Next Mass:
- News:
- Agenda:
- Services:
- Gallery:
- Contact:
- SEO:

Important files changed:
- ...

Settings/schema changes:
- None | explain and link decision

Verification:
- composer ci:check: PASS/FAIL/NOT RUN
- npm run build: PASS/FAIL/NOT RUN
- npm run e2e: PASS/FAIL/NOT RUN
- CI: PASS/FAIL/PENDING

Known limitations:
- ...

PHASE 9B remains out of scope: YES
```

---

## 15. Catatan Roadmap

Setelah PHASE 9A selesai, perbarui `docs/roadmap/README.md` untuk mencerminkan bahwa Beranda Publik telah selesai sementara PHASE 9B masih direncanakan. Jangan menandai seluruh PHASE 9 selesai sebelum Admin Dashboard diselesaikan pada dokumen phase terpisah.

Dokumen ini tidak mengizinkan PHASE 9A dinyatakan `DONE` hanya karena tampilan terlihat bagus. Kriteria data, aksesibilitas, responsive behavior, test, build, dan CI tetap wajib dipenuhi.
