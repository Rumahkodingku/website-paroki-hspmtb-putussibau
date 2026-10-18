<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Pindahkan pengaturan `website` ke grup sosial
|--------------------------------------------------------------------------
|
| Field "Situs web paroki" dipindah dari tab Profil Paroki ke tab Media Sosial
| di /admin/pengaturan. Form dibangun dari config, jadi memindahkan grup di
| `config/site-settings.php` sudah cukup untuk tampilannya; migration ini
| sekadar merapikan kolom `group` baris lama agar konsisten dengan config.
|
| Kolom `group` tidak dibaca untuk rendering (pembacaan selalu dari config),
| dan `SiteSettingsService::setMany()` akan menulis ulang grup yang benar pada
| penyimpanan berikutnya. Ini higienitas data, bukan perbaikan fungsional.
*/

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')
            ->where('key', 'website')
            ->update(['group' => 'sosial']);
    }

    public function down(): void
    {
        DB::table('site_settings')
            ->where('key', 'website')
            ->update(['group' => 'profil']);
    }
};
