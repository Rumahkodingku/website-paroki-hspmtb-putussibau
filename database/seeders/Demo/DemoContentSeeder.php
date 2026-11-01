<?php

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('demo.enabled')) {
            $this->command->warn('DemoContentSeeder dilewati: config demo.enabled = false.');

            return;
        }

        $this->call(DemoSharedMediaSeeder::class);
        $this->call(DemoAuthorSeeder::class);

        $this->call(DemoNewsSeeder::class);
        $this->call(DemoAgendaSeeder::class);
        $this->call(DemoMassScheduleSeeder::class);
        $this->call(DemoParishIdentitySeeder::class);
        $this->call(DemoCommunitiesSeeder::class);

        $this->call(DemoParishProfileSeeder::class);
        $this->call(DemoParishHistorySeeder::class);
        $this->call(DemoParishServiceAreaSeeder::class);
        $this->call(DemoAreaSeeder::class);
        $this->call(DemoPastorSeeder::class);
        $this->call(DemoParishBoardSeeder::class);

        $this->call(DemoGallerySeeder::class);
        $this->call(DemoDocumentSeeder::class);
        $this->call(DemoLivestreamingSeeder::class);

        $this->command->info('Data contoh Profil Paroki, Jadwal Misa, Berita & Agenda, Pelayanan, Komunitas, Galeri, Dokumen, dan Livestreaming dimuat.');
    }
}
