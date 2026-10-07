<?php

/*
|--------------------------------------------------------------------------
| Media
|--------------------------------------------------------------------------
|
| Everything the media pipeline needs to know, in one place. Phase 01 3.1.L asks
| for a storage abstraction, which in practice means no disk name, limit or
| variant width is written into a service or a job.
|
| GD is used directly rather than a wrapper library. The pipeline is validate,
| decode, read orientation, resize, re-encode, and every step of that is a few
| lines against GD. That keeps the dependency list unchanged, which matters for a
| project that already refuses to add a library for one isolated problem
| (see docs/DECISIONS.md D-22 for why --all is banned here).
|
| @see docs/DECISIONS.md D-24
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Disks
    |--------------------------------------------------------------------------
    |
    | Phase 01 section 19: the original file is private, published variants are
    | public. The unprocessed original must not be reachable from the browser,
    | because it still carries the EXIF the pipeline exists to remove.
    |
    */

    'original_disk' => 'local',

    'variant_disk' => 'public',

    'paths' => [
        'original' => 'media/originals',
        'variants' => 'media/variants',
    ],

    /*
    |--------------------------------------------------------------------------
    | Accepted uploads
    |--------------------------------------------------------------------------
    |
    | XC-M3: MIME is validated on the server, so the list has to be types GD can
    | actually decode. SVG is deliberately absent: it is a document that can carry
    | script, and GD cannot rasterise it safely without a separate sanitiser.
    |
    | POST-04 puts the main image at 3 MB. Note that PHP's upload_max_filesize
    | has to be at least that on the host; it is PHP_INI_PERDIR, so .env cannot
    | change it and the request would be rejected before Laravel sees it.
    |
    */

    'mime_types' => [
        'image/jpeg',
        'image/png',
    ],

    'max_upload_kilobytes' => 3072,

    /*
    | Upper bound on the original. A decompression bomb is small on disk and huge
    | in memory, so the dimensions are checked before anything is decoded.
    */
    'max_width' => 8000,

    'max_height' => 8000,

    /*
    |--------------------------------------------------------------------------
    | Variants
    |--------------------------------------------------------------------------
    |
    | Section 20 lists thumb, medium and large and says the final sizes can be
    | decided when the concrete module is built, so these are starting values.
    | Width only: resizing a portrait photo by height as well would distort it.
    |
    | An original narrower than a variant width is not upscaled. Enlarging a
    | small photo produces a bigger file that looks worse.
    |
    */

    'variants' => [
        'thumb' => 400,
        'medium' => 960,
        'large' => 1600,
    ],

    'webp_quality' => 82,

    /*
    |--------------------------------------------------------------------------
    | Job
    |--------------------------------------------------------------------------
    |
    | Three attempts with a widening gap. GD either works on a valid image or
    | fails immediately, so the backoff is not about waiting for anything; it is
    | about not hammering a disk or a worker that is already struggling.
    */

    'tries' => 3,

    'backoff' => [10, 60, 300],

];
