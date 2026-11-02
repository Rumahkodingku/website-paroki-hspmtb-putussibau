<?php

namespace Database\Seeders\Demo;

use App\Models\Media;
use App\Services\SiteSettingsService;
use Illuminate\Database\Seeder;

class DemoParishIdentitySeeder extends Seeder
{
    public function run(): void
    {
        $media = Media::query()->where('path', 'media/originals/dummy-paroki.jpg')->firstOrFail();

        app(SiteSettingsService::class)->setMany([
            'short_description' => 'Komunitas umat Katolik di Putussibau, Kabupaten Kapuas Hulu, Keuskupan Sintang.',
            'logo' => $media->getKey(),
            'logo_alt' => 'Logo Paroki',
        ]);
    }
}
