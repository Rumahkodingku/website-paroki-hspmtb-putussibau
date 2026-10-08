<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Pesan galat bawaan untuk aturan validasi. Sesuai PRD ADM-02 dan NFR-I18N,
    | seluruh pesan harus dalam Bahasa Indonesia.
    |
    | Berkas ini sengaja diterjemahkan LENGKAP (semua aturan) karena
    | config('app.fallback_locale') juga bernilai "id", sehingga tidak ada
    | jaring pengaman ke terjemahan Inggris. Test
    | tests/Feature/Foundation/ApplicationConfigurationTest.php memverifikasi
    | bahwa tidak ada aturan yang hilang.
    |
    | Placeholder seperti :attribute, :other, :value, :date, dan :max WAJIB
    | dipertahankan agar pesan tetap terisi.
    |
    */

    'accepted' => ':attribute harus disetujui.',
    'accepted_if' => ':attribute harus disetujui ketika :other bernilai :value.',
    'active_url' => ':attribute harus berupa URL yang valid.',
    'after' => ':attribute harus berisi tanggal setelah :date.',
    'after_or_equal' => ':attribute harus berisi tanggal setelah atau sama dengan :date.',
    'alpha' => ':attribute hanya boleh berisi huruf.',
    'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
    'alpha_num' => ':attribute hanya boleh berisi huruf dan angka.',
    'any_of' => ':attribute tidak valid.',
    'array' => ':attribute harus berupa daftar.',
    'array_keys' => ':attribute hanya boleh memuat kunci berikut: :values.',
    'ascii' => ':attribute hanya boleh memuat karakter dan simbol satu byte.',
    'base64' => ':attribute harus berupa teks Base64 yang valid.',
    'before' => ':attribute harus berisi tanggal sebelum :date.',
    'before_or_equal' => ':attribute harus berisi tanggal sebelum atau sama dengan :date.',
    'between' => [
        'array' => ':attribute harus memuat antara :min sampai :max item.',
        'file' => ':attribute harus berukuran antara :min sampai :max kilobyte.',
        'numeric' => ':attribute harus bernilai antara :min sampai :max.',
        'string' => ':attribute harus memuat antara :min sampai :max karakter.',
    ],
    'boolean' => ':attribute harus bernilai benar atau salah.',
    'can' => ':attribute memuat nilai yang tidak diizinkan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'contains' => ':attribute belum memuat nilai yang diperlukan.',
    'current_password' => 'Kata sandi saat ini salah.',
    'date' => ':attribute harus berisi tanggal yang valid.',
    'date_equals' => ':attribute harus berisi tanggal yang sama dengan :date.',
    'date_format' => ':attribute harus sesuai dengan format :format.',
    'decimal' => ':attribute harus memiliki :decimal angka di belakang koma.',
    'declined' => ':attribute harus ditolak.',
    'declined_if' => ':attribute harus ditolak ketika :other bernilai :value.',
    'different' => ':attribute dan :other harus berbeda.',
    'digits' => ':attribute harus terdiri dari :digits digit.',
    'digits_between' => ':attribute harus terdiri dari :min sampai :max digit.',
    'dimensions' => 'Dimensi gambar :attribute tidak valid.',
    'distinct' => ':attribute memiliki nilai yang duplikat.',
    'doesnt_contain' => ':attribute tidak boleh memuat salah satu dari berikut: :values.',
    'doesnt_end_with' => ':attribute tidak boleh diakhiri salah satu dari berikut: :values.',
    'doesnt_start_with' => ':attribute tidak boleh diawali salah satu dari berikut: :values.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'encoding' => ':attribute harus dienkode dengan :encoding.',
    'ends_with' => ':attribute harus diakhiri salah satu dari berikut: :values.',
    'enum' => ':attribute yang dipilih tidak valid.',
    'exists' => ':attribute yang dipilih tidak valid.',
    'extensions' => ':attribute harus memiliki salah satu ekstensi berikut: :values.',
    'file' => ':attribute harus berupa berkas.',
    'filled' => ':attribute harus diisi.',
    'gt' => [
        'array' => ':attribute harus memuat lebih dari :value item.',
        'file' => ':attribute harus lebih besar dari :value kilobyte.',
        'numeric' => ':attribute harus lebih besar dari :value.',
        'string' => ':attribute harus lebih dari :value karakter.',
    ],
    'gte' => [
        'array' => ':attribute harus memuat :value item atau lebih.',
        'file' => ':attribute harus lebih besar dari atau sama dengan :value kilobyte.',
        'numeric' => ':attribute harus lebih besar dari atau sama dengan :value.',
        'string' => ':attribute harus terdiri dari :value karakter atau lebih.',
    ],
    'hex_color' => ':attribute harus berupa warna heksadesimal yang valid.',
    'image' => ':attribute harus berupa gambar.',
    'in' => ':attribute yang dipilih tidak valid.',
    'in_array' => ':attribute harus terdapat dalam :other.',
    'in_array_keys' => ':attribute harus memuat setidaknya salah satu kunci berikut: :values.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'ip' => ':attribute harus berupa alamat IP yang valid.',
    'ipv4' => ':attribute harus berupa alamat IPv4 yang valid.',
    'ipv6' => ':attribute harus berupa alamat IPv6 yang valid.',
    'json' => ':attribute harus berupa JSON yang valid.',
    'list' => ':attribute harus berupa daftar.',
    'lowercase' => ':attribute harus berupa huruf kecil.',
    'lt' => [
        'array' => ':attribute harus memuat kurang dari :value item.',
        'file' => ':attribute harus lebih kecil dari :value kilobyte.',
        'numeric' => ':attribute harus lebih kecil dari :value.',
        'string' => ':attribute harus kurang dari :value karakter.',
    ],
    'lte' => [
        'array' => ':attribute tidak boleh memuat lebih dari :value item.',
        'file' => ':attribute harus lebih kecil dari atau sama dengan :value kilobyte.',
        'numeric' => ':attribute harus lebih kecil dari atau sama dengan :value.',
        'string' => ':attribute harus terdiri dari :value karakter atau kurang.',
    ],
    'mac_address' => ':attribute harus berupa alamat MAC yang valid.',
    'max' => [
        'array' => ':attribute tidak boleh memuat lebih dari :max item.',
        'file' => ':attribute tidak boleh lebih besar dari :max kilobyte.',
        'numeric' => ':attribute tidak boleh lebih besar dari :max.',
        'string' => ':attribute tidak boleh lebih dari :max karakter.',
    ],
    'max_digits' => ':attribute tidak boleh memiliki lebih dari :max digit.',
    'mimes' => ':attribute harus berupa berkas berjenis: :values.',
    'mimetypes' => ':attribute harus berupa berkas dengan tipe MIME: :values.',
    'min' => [
        'array' => ':attribute harus memuat setidaknya :min item.',
        'file' => ':attribute harus berukuran minimal :min kilobyte.',
        'numeric' => ':attribute harus bernilai minimal :min.',
        'string' => ':attribute harus terdiri dari setidaknya :min karakter.',
    ],
    'min_digits' => ':attribute harus memiliki setidaknya :min digit.',
    'missing' => ':attribute tidak boleh diisi.',
    'missing_if' => ':attribute tidak boleh diisi ketika :other bernilai :value.',
    'missing_unless' => ':attribute tidak boleh diisi kecuali :other bernilai :value.',
    'missing_with' => ':attribute tidak boleh diisi ketika :values ada.',
    'missing_with_all' => ':attribute tidak boleh diisi ketika :values ada.',
    'multiple_of' => ':attribute harus merupakan kelipatan dari :value.',
    'not_in' => ':attribute yang dipilih tidak valid.',
    'not_regex' => 'Format :attribute tidak valid.',
    'numeric' => ':attribute harus berupa angka.',
    'password' => [
        'letters' => ':attribute harus memuat setidaknya satu huruf.',
        'mixed' => ':attribute harus memuat setidaknya satu huruf besar dan satu huruf kecil.',
        'numbers' => ':attribute harus memuat setidaknya satu angka.',
        'symbols' => ':attribute harus memuat setidaknya satu simbol.',
        'uncompromised' => ':attribute ini pernah muncul dalam kebocoran data. Silakan pilih :attribute yang lain.',
    ],
    'present' => ':attribute harus diisi.',
    'present_if' => ':attribute harus diisi ketika :other bernilai :value.',
    'present_unless' => ':attribute harus diisi kecuali :other bernilai :value.',
    'present_with' => ':attribute harus diisi ketika :values ada.',
    'present_with_all' => ':attribute harus diisi ketika :values ada.',
    'prohibited' => ':attribute tidak boleh diisi.',
    'prohibited_if' => ':attribute tidak boleh diisi ketika :other bernilai :value.',
    'prohibited_if_accepted' => ':attribute tidak boleh diisi ketika :other disetujui.',
    'prohibited_if_declined' => ':attribute tidak boleh diisi ketika :other ditolak.',
    'prohibited_unless' => ':attribute tidak boleh diisi kecuali :other terdapat dalam :values.',
    'prohibits' => ':attribute melarang :other untuk diisi.',
    'regex' => 'Format :attribute tidak valid.',
    'required' => ':attribute wajib diisi.',
    'required_array_keys' => ':attribute harus memuat entri untuk: :values.',
    'required_if' => ':attribute wajib diisi ketika :other bernilai :value.',
    'required_if_accepted' => ':attribute wajib diisi ketika :other disetujui.',
    'required_if_declined' => ':attribute wajib diisi ketika :other ditolak.',
    'required_unless' => ':attribute wajib diisi kecuali :other terdapat dalam :values.',
    'required_with' => ':attribute wajib diisi ketika :values ada.',
    'required_with_all' => ':attribute wajib diisi ketika :values ada.',
    'required_without' => ':attribute wajib diisi ketika :values tidak ada.',
    'required_without_all' => ':attribute wajib diisi ketika tidak satu pun dari :values ada.',
    'same' => ':attribute harus sama dengan :other.',
    'size' => [
        'array' => ':attribute harus memuat :size item.',
        'file' => ':attribute harus berukuran :size kilobyte.',
        'numeric' => ':attribute harus bernilai :size.',
        'string' => ':attribute harus terdiri dari :size karakter.',
    ],
    'starts_with' => ':attribute harus diawali salah satu dari berikut: :values.',
    'string' => ':attribute harus berupa teks.',
    'timezone' => ':attribute harus berupa zona waktu yang valid.',
    'unique' => ':attribute sudah digunakan.',
    'uploaded' => ':attribute gagal diunggah.',
    'uppercase' => ':attribute harus berupa huruf besar.',
    'url' => ':attribute harus berupa URL yang valid.',
    'ulid' => ':attribute harus berupa ULID yang valid.',
    'uuid' => ':attribute harus berupa UUID yang valid.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Pesan validasi khusus per atribut dengan konvensi "atribut.aturan".
    | Tambahkan pesan spesifik di sini bila aturan bawaan kurang jelas
    | bagi pengelola paroki non-teknis.
    |
    */

    'custom' => [
        'nama-atribut' => [
            'nama-aturan' => 'pesan-khusus',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Mengganti placeholder :attribute menjadi istilah yang dipahami pengelola
    | paroki. Atribut yang tidak terdaftar di sini akan otomatis diubah dari
    | nama kolom (mis. "published_at" menjadi "Published at").
    |
    */

    'attributes' => [
        'address' => 'Alamat',
        'body' => 'Isi',
        'category' => 'Kategori',
        'content' => 'Isi',
        'current_password' => 'Kata sandi saat ini',
        'date' => 'Tanggal',
        'description' => 'Deskripsi',
        'document' => 'Dokumen',
        'email' => 'Email',
        'excerpt' => 'Cuplikan',
        'file' => 'Berkas',
        'image' => 'Gambar',
        'is_active' => 'Status aktif',
        'location' => 'Lokasi',
        'name' => 'Nama',
        'password' => 'Kata sandi',
        'password_confirmation' => 'Konfirmasi kata sandi',
        'phone' => 'Nomor telepon',
        'search' => 'Pencarian',
        'slug' => 'Slug',
        'status' => 'Status',
        'time' => 'Waktu',
        'title' => 'Judul',
        'url' => 'URL',
        'website' => 'Situs web',
    ],

];
