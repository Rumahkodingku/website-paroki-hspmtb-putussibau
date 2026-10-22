<?php

namespace Database\Seeders;

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DemoContentSeeder extends Seeder
{
    private const IMAGE = 'images.jpg';

    public function run(): void
    {
        if (! config('demo.enabled')) {
            $this->command->warn('DemoContentSeeder dilewati: config demo.enabled = false.');

            return;
        }

        $media = $this->dummyMedia();
        $imagePath = $media->variantPath($this->largestVariantName());
        $author = $this->demoAuthor();

        $this->call(DemoMassScheduleSeeder::class);
        $this->call(DemoNewsAndAgendaSeeder::class, false, [
            'author' => $author,
            'media' => $media,
            'imagePath' => $imagePath,
        ]);
        $this->call(DemoParishIdentitySeeder::class, false, [
            'media' => $media,
            'imagePath' => $imagePath,
        ]);
        $this->call(DemoServicesSeeder::class, false, [
            'media' => $media,
        ]);
        $this->call(DemoCommunitiesSeeder::class, false, [
            'media' => $media,
        ]);

        $this->command->info('Data contoh Profil Paroki, Jadwal Misa, Berita & Agenda, Pelayanan, dan Komunitas dimuat.');
    }

    private function dummyMedia(): Media
    {
        $source = public_path(self::IMAGE);

        if (! File::exists($source)) {
            throw new RuntimeException('Berkas '.self::IMAGE.' tidak ditemukan di folder public/.');
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

    private function largestVariantName(): string
    {
        /** @var array<string, int> $variants */
        $variants = (array) config('media.variants');

        return (string) collect($variants)->sortDesc()->keys()->first();
    }

    private function demoAuthor(): User
    {
        $author = User::query()->updateOrCreate(
            ['email' => 'redaksi-demo@example.test'],
            [
                'name' => 'Redaksi Contoh',
                'password' => Hash::make(Str::random(40)),
            ],
        );

        $author->forceFill([
            'is_active' => true,
            'email_verified_at' => now(),
        ])->save();

        return $author;
    }
}
