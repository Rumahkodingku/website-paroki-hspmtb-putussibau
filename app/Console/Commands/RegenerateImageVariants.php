<?php

namespace App\Console\Commands;

use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Services\ImageProcessor;
use App\Services\SiteSettingsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('media:regenerate-variants {--id=* : ID media yang diproses ulang} {--logo : Proses ulang media logo paroki}')]
#[Description('Regenerate WebP variants for existing media, optionally the parish logo')]
class RegenerateImageVariants extends Command
{
    public function handle(): int
    {
        $ids = array_map('intval', (array) $this->option('id'));

        if ($this->option('logo')) {
            $logo = app(SiteSettingsService::class)->get('logo');

            if (! is_string($logo) || trim($logo) === '') {
                $this->components->error('Setting logo belum diisi.');

                return self::FAILURE;
            }

            $ids[] = (int) $logo;
        }

        if ($ids === []) {
            /** @var list<int> $ids */
            $ids = Media::query()->pluck('id')->map(intval(...))->all();
        }

        $ids = array_values(array_unique($ids));

        if ($ids === []) {
            $this->components->warn('Tidak ada media untuk diproses.');

            return self::SUCCESS;
        }

        $processor = app(ImageProcessor::class);

        foreach ($ids as $id) {
            (new GenerateImageVariants($id))->handle($processor);
        }

        $this->components->info(sprintf('Memproses ulang %d media.', count($ids)));

        return self::SUCCESS;
    }
}
