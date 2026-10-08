<?php

namespace App\Jobs;

use App\Enums\MediaStatus;
use App\Models\Media;
use App\Services\ImageProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Generates the published variants for one uploaded image.
 *
 * Phase 01 section 21 and XC-M1 both require this to run through the queue rather
 * than inside the upload request: decoding and resizing three sizes is not
 * something a request should wait for, and a slow upload request times out and
 * leaves the uploader with no idea what happened.
 *
 * Written to be safe to retry. Every step is idempotent: the media row is looked
 * up fresh, inspect() overwrites the dimensions, and recordVariant() uses
 * updateOrCreate behind a unique index. Running it twice produces the same files,
 * not a duplicate set and not a crash.
 *
 * @see docs/DECISIONS.md D-24
 */
class GenerateImageVariants implements ShouldQueue
{
    use Queueable;

    /**
     * Attempts before the row is marked failed. GD either works on a valid image
     * or fails at once, so this is about not hammering a struggling disk, not
     * about waiting for something to become available.
     */
    public int $tries;

    /**
     * @var list<int>
     */
    public array $backoff;

    public function __construct(public readonly int $mediaId)
    {
        $this->tries = (int) config('media.tries');
        // array_values plus an int cast, because config() returns a plain array
        // and the property is a list of seconds.
        $this->backoff = array_values(array_map(
            intval(...),
            (array) config('media.backoff'),
        ));
    }

    /**
     * Run the pipeline.
     */
    public function handle(ImageProcessor $processor): void
    {
        $media = Media::query()->find($this->mediaId);

        if ($media === null) {
            // The row was deleted between the dispatch and now. Nothing to do
            // and nothing to retry.
            Log::warning('GenerateImageVariants skipped: the media row is gone.', [
                'media_id' => $this->mediaId,
            ]);

            return;
        }

        $media->forceFill(['status' => MediaStatus::Processing, 'error_message' => null])->save();

        $processor->inspect($media);
        $variants = $processor->variants($media);

        $media->forceFill([
            'status' => MediaStatus::Ready,
            'error_message' => null,
        ])->save();

        Log::info('Media processing finished.', [
            'media_id' => $media->getKey(),
            'variants' => array_map(fn ($variant): string => $variant->name, $variants),
        ]);
    }

    /**
     * Record the failure without taking the row down with it.
     *
     * Phase 01 section 21 asks for failed_jobs, no database damage, and a log
     * worth reading. The original file is kept: it is the only way to retry by
     * hand, and deleting it would make a transient failure permanent.
     */
    public function failed(?Throwable $exception): void
    {
        $media = Media::query()->find($this->mediaId);

        $media?->forceFill([
            'status' => MediaStatus::Failed,
            'error_message' => $exception?->getMessage(),
        ])->save();

        Log::error('Media processing failed.', [
            'media_id' => $this->mediaId,
            'exception' => $exception === null ? null : $exception::class,
            'message' => $exception?->getMessage(),
        ]);
    }
}
