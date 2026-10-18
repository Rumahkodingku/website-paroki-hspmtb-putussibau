<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Konsolidasi identitas paroki ke site_settings
|--------------------------------------------------------------------------
|
| Modul Informasi Profil dihapus. Migration ini:
|
| 1. Membuat tabel singleton baru `parish_history_narratives` untuk narasi
|    sejarah yang sebelumnya tinggal di `parish_profiles.history_description`.
| 2. Menyalin narasi dan field profil yang belum punya padanan ke
|    `site_settings` — hanya bila key tersebut masih kosong ("settings
|    menang"). `parish_name`, `contact_*`, dan `maps_link` tidak ditimpa.
| 3. Menghapus tabel `parish_profiles`.
|
| Backfill memakai query builder, bukan model, karena model `ParishProfile`
| ikut dihapus pada perubahan yang sama.
|
| @see docs/DECISIONS.md D-69
| @see docs/plan-implementation/plan-revisi-konsolidasi-identitas-paroki.md
*/

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parish_history_narratives', function (Blueprint $table) {
            $table->id();
            // Narasi utama sejarah. HTML tersanitasi disimpan apa adanya;
            // sanitasi berjalan di form request, bukan di sini.
            $table->longText('narrative')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('parish_profiles')) {
            $this->backfillFromProfile();
        }

        Schema::dropIfExists('parish_profiles');

        // Baris legacy yang tidak lagi dikenal config/site-settings.php.
        DB::table('site_settings')->where('key', 'maps_embed_url')->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('parish_history_narratives');

        // Tabel lama dipulihkan agar rollback tidak error. Data profil yang
        // sudah dikonsolidasikan ke settings tidak dipulihkan.
        if (! Schema::hasTable('parish_profiles')) {
            Schema::create('parish_profiles', function (Blueprint $table) {
                $table->id();

                $table->string('name', 150);
                $table->text('short_description')->nullable();
                $table->longText('description')->nullable();
                $table->longText('history_description')->nullable();

                $table->text('address')->nullable();
                $table->string('phone', 50)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('website', 255)->nullable();
                $table->unsignedSmallInteger('established_year')->nullable();

                $table->foreignId('logo_media_id')->nullable()->constrained('media')->nullOnDelete();
                $table->string('logo_alt', 255)->nullable();
                $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
                $table->string('cover_alt', 255)->nullable();

                $table->string('map_url', 500)->nullable();
                $table->string('video_url', 500)->nullable();

                $table->boolean('is_published')->default(false);

                $table->timestamps();
            });
        }
    }

    /**
     * Salin field profil yang belum punya padanan terisi ke site_settings.
     *
     * Aturan: hanya key yang masih kosong yang diisi. Field identitas/kontak
     * global (parish_name, contact_*, maps_link) sengaja tidak disertakan
     * karena site settings adalah sumber kanonisnya.
     */
    private function backfillFromProfile(): void
    {
        $profile = DB::table('parish_profiles')->first();

        if ($profile === null) {
            return;
        }

        if (
            DB::table('parish_history_narratives')->count() === 0
            && $profile->history_description !== null
        ) {
            DB::table('parish_history_narratives')->insert([
                'narrative' => $profile->history_description,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $groups = $this->groupByKey();

        /** @var array<string, mixed> $mappings */
        $mappings = [
            'short_description' => $profile->short_description,
            'description' => $profile->description,
            'established_year' => $profile->established_year,
            'video_url' => $profile->video_url,
            'website' => $profile->website,
            'logo' => $profile->logo_media_id,
            'logo_alt' => $profile->logo_alt,
            'cover' => $profile->cover_media_id,
            'cover_alt' => $profile->cover_alt,
            'is_published' => $profile->is_published,
        ];

        foreach ($mappings as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }

            $alreadySet = DB::table('site_settings')
                ->where('key', $key)
                ->whereNotNull('value')
                ->where('value', '!=', '')
                ->exists();

            if ($alreadySet) {
                continue;
            }

            DB::table('site_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => (string) $value,
                    'group' => $groups[$key] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    /**
     * Map setiap key pengaturan ke grupnya dari konfigurasi.
     *
     * @return array<string, string>
     */
    private function groupByKey(): array
    {
        $map = [];

        foreach ((array) config('site-settings.groups', []) as $group => $keys) {
            foreach ((array) $keys as $key) {
                $map[$key] = (string) $group;
            }
        }

        return $map;
    }
};
