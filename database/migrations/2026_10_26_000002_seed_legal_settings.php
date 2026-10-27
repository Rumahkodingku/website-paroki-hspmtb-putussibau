<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $rows = [
            [
                'key' => 'privacy_policy_content',
                'group' => 'privasi',
                'value' => '<p>Kebijakan Privasi ini menjelaskan bagaimana Paroki HSPMTB Putussibau mengelola informasi yang Anda berikan saat menggunakan situs ini.</p><p>Kami hanya menggunakan data yang diperlukan untuk pelayanan paroki, seperti permintaan informasi, pendaftaran pelayanan, dan komunikasi pastoral. Data tidak dibagikan kepada pihak lain kecuali untuk keperluan pelayanan yang Anda minta atau jika diwajibkan oleh ketentuan yang berlaku.</p><p>Situs ini dapat menautkan ke layanan pihak ketiga. Kebijakan privasi masing-masing layanan berlaku saat Anda mengunjungi tautan tersebut. Jika ada pertanyaan, hubungi sekretariat paroki.</p>',
            ],
            [
                'key' => 'terms_and_conditions_content',
                'group' => 'syarat',
                'value' => '<p>Syarat dan Ketentuan ini mengatur penggunaan situs Paroki HSPMTB Putussibau. Dengan mengakses situs ini, Anda setuju untuk mematuhi ketentuan yang berlaku.</p><ul><li>Informasi di situs ini disediakan untuk kebutuhan pastoral dan informasi paroki.</li><li>Pengguna tidak boleh menyalahgunakan situs, termasuk menyebarkan konten yang melanggar hukum atau mengganggu pelayanan paroki.</li><li>Paroki dapat memperbarui isi situs dan ketentuan ini sewaktu-waktu.</li></ul><p>Jika ada pertanyaan, hubungi sekretariat paroki.</p>',
            ],
        ];

        foreach ($rows as $row) {
            $existing = DB::table('site_settings')->where('key', $row['key'])->first();

            if ($existing === null) {
                DB::table('site_settings')->insert([
                    'key' => $row['key'],
                    'value' => $row['value'],
                    'group' => $row['group'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                continue;
            }

            if ($existing->value === null || trim((string) $existing->value) === '') {
                DB::table('site_settings')
                    ->where('key', $row['key'])
                    ->update([
                        'value' => $row['value'],
                        'group' => $row['group'],
                        'updated_at' => $now,
                    ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('site_settings')->whereIn('key', [
            'privacy_policy_content',
            'terms_and_conditions_content',
        ])->delete();
    }
};
