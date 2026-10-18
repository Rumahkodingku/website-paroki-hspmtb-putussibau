<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Bersihkan nilai favicon lama yang bukan media id
|--------------------------------------------------------------------------
|
| `site_settings.favicon` berubah makna dari URL string menjadi media id
| (D-69). Nilai lama yang bukan angka tidak akan pernah cocok dengan baris
| media mana pun, jadi dibiarkan akan terbaca sebagai "tidak ada favicon".
| Migration ini men-null-kannya secara eksplisit agar nilainya jujur.
|
| Tidak ada `down()` yang memulihkan: nilai lama sudah tidak bermakna dan
| tidak disimpan di tempat lain.
*/

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')
            ->where('key', 'favicon')
            ->whereNotNull('value')
            ->whereRaw("value NOT REGEXP '^[0-9]+$'")
            ->update(['value' => null]);
    }

    public function down(): void
    {
        // Tidak ada yang dipulihkan: nilai lama sudah dibuang.
    }
};
