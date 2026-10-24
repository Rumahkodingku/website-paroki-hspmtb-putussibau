<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Konten profil paroki kembali ke modul Profil Paroki
|--------------------------------------------------------------------------
|
| D-69 memindahkan konten editorial profil ke site_settings dan menghapus
| modul Informasi Profil. Migration ini mengembalikannya: satu tabel singleton
| `parish_profiles` untuk deskripsi, tahun berdiri, sampul, dan video.
|
| Identitas dan kontak global tetap di site_settings — hanya field konten yang
| dipindah. `is_published` tidak dibuat ulang karena halaman publik profil
| sudah memakai scope published milik modulnya masing-masing (D-78).
|
| Backfill membaca baris setting yang ada, lalu menghapusnya. Nilai yang sudah
| diisi admin tidak hilang.
|
| @see docs/DECISIONS.md D-78
*/

return new class extends Migration
{
    /**
     * The setting keys owned by the profile content, mapped to columns.
     *
     * @var array<string, string>
     */
    private array $keys = [
        'description' => 'description',
        'established_year' => 'established_year',
        'cover' => 'cover_media_id',
        'cover_alt' => 'cover_alt',
        'video_url' => 'video_url',
    ];

    public function up(): void
    {
        Schema::create('parish_profiles', function (Blueprint $table) {
            $table->id();

            // Sanitized HTML from the editor. Sanitization runs in the form
            // request, not here.
            $table->longText('description')->nullable();
            $table->unsignedSmallInteger('established_year')->nullable();

            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->string('cover_alt', 255)->nullable();
            $table->string('video_url', 500)->nullable();

            $table->timestamps();
        });

        $this->backfillFromSettings();
    }

    public function down(): void
    {
        $profile = DB::table('parish_profiles')->first();

        if ($profile !== null) {
            $values = [
                'description' => $profile->description,
                'established_year' => $profile->established_year,
                'cover' => $profile->cover_media_id,
                'cover_alt' => $profile->cover_alt,
                'video_url' => $profile->video_url,
            ];

            foreach ($values as $key => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                DB::table('site_settings')->updateOrInsert(
                    ['key' => $key],
                    [
                        'value' => (string) $value,
                        'group' => 'profil',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            }
        }

        Schema::dropIfExists('parish_profiles');
    }

    private function backfillFromSettings(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $settings = DB::table('site_settings')
            ->whereIn('key', array_keys($this->keys))
            ->pluck('value', 'key');

        if ($settings->isEmpty()) {
            return;
        }

        $values = [];

        foreach ($this->keys as $key => $column) {
            $value = $settings->get($key);

            if ($value === null || $value === '') {
                continue;
            }

            $values[$column] = $column === 'cover_media_id'
                ? (int) $value
                : $value;
        }

        if ($values !== []) {
            DB::table('parish_profiles')->insert([
                ...$values,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // is_published is intentionally dropped with the rest of the group.
        DB::table('site_settings')
            ->whereIn('key', [...array_keys($this->keys), 'is_published'])
            ->delete();
    }
};
