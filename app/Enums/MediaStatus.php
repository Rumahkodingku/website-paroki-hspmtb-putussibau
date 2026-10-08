<?php

namespace App\Enums;

/**
 * Where an upload is in the pipeline.
 *
 * The row is created the moment the original file lands on disk, before any
 * variant exists, so there is always something for the admin UI to read a
 * status from. XC-M1 requires that UI to show processing state.
 */
enum MediaStatus: string
{
    /** Stored on disk, job not picked up yet. */
    case Pending = 'pending';

    /** The GenerateImageVariants job is running. */
    case Processing = 'processing';

    /** At least one variant was written. */
    case Ready = 'ready';

    /** The job gave up. The row and the original file are kept, see Media::$errorMessage. */
    case Failed = 'failed';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
