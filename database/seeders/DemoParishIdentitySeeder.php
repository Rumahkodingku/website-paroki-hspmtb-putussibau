<?php

namespace Database\Seeders;

use App\Enums\PastorPosition;
use App\Models\Media;
use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use App\Models\ParishHistoryItem;
use App\Models\ParishHistoryNarrative;
use App\Models\ParishServiceArea;
use App\Models\Pastor;
use App\Models\PastorAssignment;
use App\Services\SiteSettingsService;
use Illuminate\Database\Seeder;

class DemoParishIdentitySeeder extends Seeder
{
    public function run(Media $media, ?string $imagePath): void
    {
        app(SiteSettingsService::class)->setMany([
            'short_description' => 'Komunitas umat Katolik di Putussibau, Kabupaten Kapuas Hulu, Keuskupan Sintang.',
            'description' => '<p>Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau '
                .'melayani umat di wilayah Putussibau dan sekitarnya. '
                .'Hidup menggereja diwujudkan melalui peribadatan, katekese, dan karya sosial.</p>',
            'established_year' => 1956,
            'logo' => $media->getKey(),
            'logo_alt' => 'Logo Paroki',
            'cover' => $media->getKey(),
            'cover_alt' => 'Gambar sampul Paroki',
            'is_published' => true,
        ]);

        ParishHistoryNarrative::query()->updateOrCreate(
            [],
            [
                'narrative' => '<p>Paroki ini bertumbuh dari komunitas kecil umat menjadi '
                    .'paroki yang melayani beberapa stasi dan lingkungan. Perjalanan tersebut '
                    .'menjadi bagian dari sejarah iman umat di Kapuas Hulu.</p>',
            ],
        );

        ParishServiceArea::query()->updateOrCreate(
            [],
            [
                'content' => '<p>Wilayah pelayanan paroki mencakup umat di sekitar Putussibau '
                    .'beserta stasi dan kapel yang berada di bawahnya. '
                    .'Pembagian wilayah dan lingkungan memudahkan pendampingan umat.</p>',
                'map_media_id' => $media->getKey(),
                'map_url' => null,
                'is_published' => true,
            ],
        );

        /** @var list<array{title: string, year: int, description: string, sort: int}> $history */
        $history = [
            ['title' => 'Paroki mulai dilayani secara tetap', 'year' => 1956, 'description' => '<p>Umat mulai dilayani secara tetap oleh seorang imam.</p>', 'sort' => 1],
            ['title' => 'Pembangunan gereja paroki', 'year' => 1972, 'description' => '<p>Gereja paroki dibangun dan diberkati bersama seluruh umat.</p>', 'sort' => 2],
            ['title' => 'Pembentukan stasi dan lingkungan', 'year' => 1990, 'description' => '<p>Wilayah pelayanan ditata ke dalam stasi dan lingkungan.</p>', 'sort' => 3],
            ['title' => 'Renovasi dan penataan pastoral', 'year' => 2015, 'description' => '<p>Gereja direnovasi dan tata kelola pastoral diperbarui.</p>', 'sort' => 4],
        ];

        foreach ($history as $item) {
            ParishHistoryItem::query()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'year' => $item['year'],
                    'date' => null,
                    'description' => $item['description'],
                    'media_id' => $media->getKey(),
                    'image_alt' => 'Foto perjalanan sejarah paroki',
                    'sort_order' => $item['sort'],
                    'is_published' => true,
                ],
            );
        }

        $this->seedPastors($media);
        $this->seedBoard($media);
    }

    private function seedPastors(Media $media): void
    {
        $current = Pastor::query()->updateOrCreate(
            ['name' => 'RD. Yohanes Baptista'],
            [
                'title' => 'Pst.',
                'photo_media_id' => $media->getKey(),
                'photo_alt' => 'Foto pastor paroki',
                'bio' => '<p>Melayani sebagai pastor paroki dan mendampingi umat di wilayah Putussibau.</p>',
                'sort_order' => 1,
                'is_published' => true,
            ],
        );

        $previous = Pastor::query()->updateOrCreate(
            ['name' => 'RD. Petrus Kanisius'],
            [
                'title' => 'Pst.',
                'photo_media_id' => $media->getKey(),
                'photo_alt' => 'Foto pastor terdahulu',
                'bio' => '<p>Pernah melayani sebagai pastor paroki pada periode sebelumnya.</p>',
                'sort_order' => 2,
                'is_published' => true,
            ],
        );

        PastorAssignment::query()->update(['is_current' => false]);

        PastorAssignment::query()->updateOrCreate(
            ['pastor_id' => $previous->getKey(), 'start_year' => 2008],
            [
                'position' => PastorPosition::PastorParoki,
                'end_year' => 2016,
                'is_current' => false,
                'description' => 'Masa penugasan sebagai pastor paroki.',
            ],
        );

        PastorAssignment::query()->updateOrCreate(
            ['pastor_id' => $current->getKey(), 'start_year' => 2016],
            [
                'position' => PastorPosition::PastorParoki,
                'end_year' => null,
                'is_current' => true,
                'description' => 'Sedang bertugas sebagai pastor paroki.',
            ],
        );

        PastorAssignment::query()->updateOrCreate(
            ['pastor_id' => $current->getKey(), 'start_year' => 2010],
            [
                'position' => PastorPosition::PastorRekan,
                'end_year' => 2016,
                'is_current' => false,
                'description' => 'Masa penugasan sebagai pastor rekan.',
            ],
        );
    }

    private function seedBoard(Media $media): void
    {
        ParishBoardPeriod::query()->update(['is_active' => false]);

        $active = ParishBoardPeriod::query()->updateOrCreate(
            ['name' => 'Periode 2023–2026'],
            [
                'start_year' => 2023,
                'end_year' => 2026,
                'description' => 'Periode kepengurusan yang sedang berjalan.',
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 1,
            ],
        );

        $archive = ParishBoardPeriod::query()->updateOrCreate(
            ['name' => 'Periode 2020–2023'],
            [
                'start_year' => 2020,
                'end_year' => 2023,
                'description' => 'Periode kepengurusan sebelumnya.',
                'is_active' => false,
                'is_published' => true,
                'sort_order' => 2,
            ],
        );

        /** @var list<array{0: ParishBoardPeriod, 1: string, 2: string, 3: int}> $members */
        $members = [
            [$active, 'Antonius Sudirman', 'Ketua', 1],
            [$active, 'Maria Fransiska', 'Wakil Ketua', 2],
            [$active, 'Yohanes Hendra', 'Sekretaris', 3],
            [$active, 'Theresia Lestari', 'Bendahara', 4],
            [$archive, 'Paulus Gunawan', 'Ketua', 1],
            [$archive, 'Agnes Ratna', 'Sekretaris', 2],
        ];

        foreach ($members as [$period, $name, $position, $sort]) {
            ParishBoardMember::query()->updateOrCreate(
                ['parish_board_period_id' => $period->getKey(), 'name' => $name],
                [
                    'position' => $position,
                    'photo_media_id' => $media->getKey(),
                    'photo_alt' => 'Foto pengurus paroki',
                    'description' => null,
                    'sort_order' => $sort,
                    'is_published' => true,
                ],
            );
        }
    }
}
