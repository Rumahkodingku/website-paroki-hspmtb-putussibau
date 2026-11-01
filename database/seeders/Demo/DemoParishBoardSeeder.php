<?php

namespace Database\Seeders\Demo;

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Models\ParishBoardMember;
use App\Models\ParishBoardPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Seeds the Parish Board (Dewan Pastoral Paroki) structure with data
 * compiled from public sources.
 *
 * Demo-only: called by DemoContentSeeder when config('demo.enabled') is true.
 * Periods and members are updated in place so the seeder is idempotent.
 */
class DemoParishBoardSeeder extends Seeder
{
    private const IMAGE = 'illustrations/demo/pengurus-paroki-man.png';

    public function run(): void
    {
        $media = $this->dummyMedia();

        // Deactivate all periods first, then re-activate the current one.
        ParishBoardPeriod::query()->update(['is_active' => false]);

        $period2021 = ParishBoardPeriod::query()->updateOrCreate(
            ['name' => 'Periode 2021–2024'],
            [
                'start_year' => 2021,
                'end_year' => 2024,
                'description' => 'Periode kepengurusan Dewan Pastoral Paroki HSPMTB Putussibau yang tercatat dalam pemberitaan pelantikan pada Juni 2021.',
                'is_active' => false,
                'is_published' => true,
                'sort_order' => 2,
            ],
        );

        $period2025 = ParishBoardPeriod::query()->updateOrCreate(
            ['name' => 'Periode 2025'],
            [
                'start_year' => 2025,
                'end_year' => null,
                'description' => 'Periode kepengurusan Dewan Pastoral Paroki HSPMTB Putussibau yang tercatat dalam dokumen paroki tahun 2025.',
                'is_active' => true,
                'is_published' => true,
                'sort_order' => 1,
            ],
        );

        /** @var list<array{0: ParishBoardPeriod, 1: string, 2: string, 3: int, 4: string}> $members */
        $members = [
            [$period2021, 'Firdaus Lie', 'Ketua Dewan Pastoral Paroki', 1, 'Tercatat sebagai Ketua DPP dalam dokumen paroki tahun 2021 dan 2022.'],
            [$period2021, 'Yohanes Telajan', 'Sekretaris I', 2, 'Tercatat sebagai Sekretaris I dalam dokumen paroki tahun 2022.'],
            [$period2021, 'Martinus Tamur', 'Pastor Rekan', 3, 'Pastor Rekan sebelum pergantian yang diberitakan pada Januari 2020.'],
            [$period2021, 'Blasius Lion', 'Pastor Rekan', 4, 'Menggantikan RP. Martinus Tamur, SMM sebagai Pastor Rekan berdasarkan pemberitaan Januari 2020.'],
            [$period2025, 'Yovinus Riady', 'Ketua Dewan Pastoral Paroki', 1, 'Tercatat sebagai Ketua DPP dalam dokumen kegiatan BKSN Paroki HSPMTB Putussibau tanggal 18 September 2025.'],
            [$period2025, 'Yakobus Rua Bai', 'Pastor Paroki', 2, 'Tercatat sebagai Pastor Paroki dalam pemberitaan Pemerintah Kabupaten Kapuas Hulu, Januari 2020.'],
            [$period2025, 'Bronislawa Saryono Nasredin', 'Pastor Rekan', 3, 'Tercatat sebagai anggota komunitas SMM Putussibau; periode dan jabatan pastoral di HSPMTB perlu dikonfirmasi.'],
        ];

        foreach ($members as [$period, $name, $position, $sort, $description]) {
            ParishBoardMember::query()->updateOrCreate(
                ['parish_board_period_id' => $period->getKey(), 'name' => $name],
                [
                    'position' => $position,
                    'photo_media_id' => $media->getKey(),
                    'photo_alt' => $name.', '.$position.' Paroki HSPMTB Putussibau',
                    'description' => $description,
                    'sort_order' => $sort,
                    'is_published' => true,
                ],
            );
        }
    }

    private function dummyMedia(): Media
    {
        $source = public_path(self::IMAGE);

        if (! File::exists($source)) {
            throw new \RuntimeException('Berkas '.self::IMAGE.' tidak ditemukan di folder public/.');
        }

        $disk = (string) config('media.original_disk');
        $path = 'media/originals/dummy-pengurus.png';

        Storage::disk($disk)->makeDirectory(dirname($path));
        Storage::disk($disk)->put($path, (string) File::get($source));

        $media = Media::query()->updateOrCreate(
            ['path' => $path],
            [
                'disk' => $disk,
                'original_name' => 'pengurus-paroki-man.png',
                'mime_type' => 'image/png',
                'file_size' => File::size($source),
                'width' => null,
                'height' => null,
                'alt_text' => 'Foto pengurus paroki',
                'status' => MediaStatus::Pending,
                'uploader_id' => null,
                'error_message' => null,
            ],
        );

        GenerateImageVariants::dispatchSync($media->getKey());

        return $media->fresh()->load('variants');
    }
}
