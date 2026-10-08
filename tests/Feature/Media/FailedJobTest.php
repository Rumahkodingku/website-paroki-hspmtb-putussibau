<?php

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Failed jobs
|--------------------------------------------------------------------------
|
| Test Matrix covered here:
|
|   - T26 a failed job is recorded
|
| This file exists because T26 was never actually covered. MediaTest has a test
| called "a failure marks the row failed and keeps the original", and it calls
| ->failed() by hand. That runs the handler without ever going through the queue,
| so it proved the handler works and said nothing about whether a failing job
| ever reaches failed_jobs at all. The matrix row was being satisfied by a test
| that could not have failed the way the matrix describes.
|
| So these tests run a real worker over a real queued job and assert on the rows
| that land in the failed_jobs table. Nothing here is faked except the disks.
|
| Roadmap section 21 asks for three things when a job fails: it reaches
| failed_jobs, it does not damage the database, and it logs something worth
| reading. Each has its own test below.
|
| @see docs/DECISIONS.md D-26
|
*/

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    /*
     * The real queue, not the test default.
     *
     * phpunit.xml sets QUEUE_CONNECTION=sync so the rest of the suite does not
     * have to drain a queue. Under sync, dispatch() runs the job inline and a
     * thrown exception propagates straight into the test, so nothing ever
     * reaches failed_jobs. That setting is why every earlier media test had to
     * call the job by hand, and why T26 could not have been covered properly
     * without this.
     */
    config(['queue.default' => 'database']);

    /*
     * One attempt and no backoff.
     *
     * The job reads its own $tries and $backoff from config in its constructor,
     * so this has to be set before the job is constructed, not before it runs.
     * With the production values the job would be released back to the queue and
     * retried over 370 seconds, and the test would hang rather than fail.
     */
    config([
        'media.tries' => 1,
        'media.backoff' => [0],
    ]);
});

/**
 * A media row whose original is registered on disk but not actually there.
 *
 * The realistic version of this is a disk that filled up, a file removed by
 * hand, or a deployment that replaced the volume. It is also the cheapest way to
 * make ImageProcessor throw without teaching the pipeline about a special test
 * case.
 */
function mediaWithMissingOriginal(): Media
{
    return Media::factory()->create([
        'disk' => (string) config('media.original_disk'),
        'path' => (string) config('media.paths.original').'/'.Str::uuid().'.jpg',
        'original_name' => 'hilang.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 1024,
    ]);
}

/**
 * Run the queue until the queued job has been dealt with.
 *
 * Not Queue::fake(). A fake records the dispatch and nothing else, which is
 * precisely the gap this file exists to close.
 */
function drainQueue(): void
{
    test()->artisan('queue:work', [
        '--once' => true,
        '--stop-when-empty' => true,
        '--timeout' => 30,
    ])->assertExitCode(0);
}

test('a job that fails is recorded in failed_jobs', function () {
    // T26
    $media = mediaWithMissingOriginal();

    dispatch(new GenerateImageVariants($media->getKey()));

    drainQueue();

    expect(DB::table('failed_jobs')->count())->toBe(1);
});

test('the recorded failure names the job that failed', function () {
    $media = mediaWithMissingOriginal();

    dispatch(new GenerateImageVariants($media->getKey()));

    drainQueue();

    $row = DB::table('failed_jobs')->first();

    /*
     * The payload is JSON, and the job inside it is PHP-serialised. Both are
     * Laravel's own formats: the failed job provider stores the queue payload
     * verbatim, and the database queue driver serialises the command. queue:retry
     * reads them back the same way, so this is the round trip that has to work
     * or the row is unreadable in practice.
     */
    $payload = json_decode((string) $row->payload, true);
    $job = unserialize((string) data_get($payload, 'data.command'));

    expect($row->uuid)->not->toBeNull()
        ->and($row->connection)->toBe('database')
        ->and($row->queue)->not->toBeNull()
        ->and($row->failed_at)->not->toBeNull()
        // The uuid column and the payload uuid have to agree; queue:retry
        // matches on it.
        ->and($payload['uuid'])->toBe($row->uuid)
        ->and($payload['displayName'])->toBe(GenerateImageVariants::class)
        ->and($job)->toBeInstanceOf(GenerateImageVariants::class)
        ->and($job->mediaId)->toBe($media->getKey());
});

test('the recorded failure keeps a readable reason', function () {
    // The exception column is the whole reason the row exists. A row that only
    // says the job failed makes an administrator guess.
    $media = mediaWithMissingOriginal();

    dispatch(new GenerateImageVariants($media->getKey()));

    drainQueue();

    $exception = (string) DB::table('failed_jobs')->value('exception');

    expect($exception)->toContain('Gambar pada media #'.$media->getKey().' tidak dapat dibaca.')
        ->and($exception)->toContain('UnprocessableImage');
});

test('a failed job leaves the database intact', function () {
    // Roadmap section 21: no database damage. The row is still there, marked
    // failed, holding the original's details.
    $media = mediaWithMissingOriginal();

    dispatch(new GenerateImageVariants($media->getKey()));

    drainQueue();

    /*
     * Read through value() rather than through find().
     *
     * find() is typed Model|Collection, so every property access on it is an
     * error even though a primary-key lookup can only ever return one model.
     * Asking the query for the column sidesteps the union instead of casting
     * around it.
     */
    expect(Media::query()->whereKey($media->getKey())->value('status'))
        ->toBe(MediaStatus::Failed)
        ->and(Media::query()->whereKey($media->getKey())->value('original_name'))
        ->toBe('hilang.jpg');
});

test('a failed job is not left behind in the queue', function () {
    $media = mediaWithMissingOriginal();

    dispatch(new GenerateImageVariants($media->getKey()));

    expect(DB::table('jobs')->count())->toBe(1);

    drainQueue();

    expect(DB::table('jobs')->count())->toBe(0);
});

test('a successful job leaves failed_jobs empty', function () {
    // The other direction. Without this, a test that passed because the worker
    // never ran at all would look identical to one that passed because the
    // pipeline worked.
    $path = 'media/originals/'.Str::uuid().'.jpg';

    Storage::disk((string) config('media.original_disk'))->put($path, jpegBytes());

    $media = Media::factory()->create([
        'disk' => (string) config('media.original_disk'),
        'path' => $path,
        'mime_type' => 'image/jpeg',
    ]);

    dispatch(new GenerateImageVariants($media->getKey()));

    drainQueue();

    expect(DB::table('failed_jobs')->count())->toBe(0)
        ->and(Media::query()->whereKey($media->getKey())->value('status'))
        ->toBe(MediaStatus::Ready);
});

test('the worker really is the thing under test', function () {
    // Guards the guard: if drainQueue() stopped processing, both tests above
    // would still pass in the failure case, because failed_jobs would be empty
    // rather than populated.
    $media = mediaWithMissingOriginal();

    Queue::push(new GenerateImageVariants($media->getKey()));

    drainQueue();

    expect(DB::table('failed_jobs')->count())->toBe(1);
});

/**
 * A real JPEG, generated rather than committed.
 */
function jpegBytes(): string
{
    $image = imagecreatetruecolor(80, 40);
    imagefilledrectangle($image, 0, 0, 80, 40, truecolor($image, 10, 90, 200));

    ob_start();
    imagejpeg($image, null, 90);
    imagedestroy($image);

    return (string) ob_get_clean();
}
