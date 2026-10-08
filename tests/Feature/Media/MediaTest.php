<?php

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Models\MediaVariant;
use App\Models\User;
use App\Services\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Media
|--------------------------------------------------------------------------
|
| Covers T19 through T23 of the Phase 01 test matrix: invalid MIME rejected,
| oversized rejected, the job runs, WebP output exists, EXIF stripped.
|
| Every image here is generated at run time rather than committed as a fixture,
| so the file a test operates on is visible in the test. The one exception is the
| EXIF fixture, which has to be assembled by hand because GD cannot write EXIF.
|
| @see docs/DECISIONS.md D-24
|
*/

/*
|--------------------------------------------------------------------------
| Fixtures
|--------------------------------------------------------------------------
*/

/**
 * A plain JPEG of the given size.
 */
function jpegUpload(int $width = 1200, int $height = 600, string $name = 'foto.jpg'): UploadedFile
{
    // max(1, ...) because imagecreatetruecolor declares int<1, max>. A fixture
    // asking for a zero-pixel image is a mistake in the test, not something to
    // hand to GD and let it fail on.
    $image = imagecreatetruecolor(max(1, $width), max(1, $height));
    imagefilledrectangle($image, 0, 0, $width, $height, truecolor($image, 200, 30, 40));

    $path = tempnam(sys_get_temp_dir(), 'media').'.jpg';
    imagejpeg($image, $path, 90);
    imagedestroy($image);

    return new UploadedFile($path, $name, 'image/jpeg', null, true);
}

/**
 * A JPEG carrying an EXIF APP1 segment with an Orientation tag and a GPS entry.
 *
 * Spliced together byte by byte because GD has no way to write EXIF, and a
 * committed binary fixture would be a blob nobody could review. The result is a
 * minimal but genuine EXIF block: exif_read_data() reports Orientation and
 * GPSLatitudeRef on it.
 */
function jpegUploadWithExif(int $orientation = 6, string $name = 'exif.jpg'): UploadedFile
{
    $plain = jpegUpload(40, 20, $name)->getRealPath();
    $base = (string) file_get_contents($plain);

    $be16 = fn (int $value): string => pack('n', $value);
    $be32 = fn (int $value): string => pack('N', $value);

    $ifd0 = 8;
    $gpsIfd = $ifd0 + 2 + (2 * 12) + 4;

    $tiff = 'MM'.$be16(0x002A).$be32($ifd0);
    $tiff .= $be16(2);                                                      // IFD0 entries
    $tiff .= $be16(0x0112).$be16(3).$be32(1).$be32($orientation << 16);     // Orientation
    $tiff .= $be16(0x8825).$be16(4).$be32(1).$be32($gpsIfd);               // GPS IFD pointer
    $tiff .= $be32(0);                                                      // no IFD1

    $tiff .= $be16(1);                                                      // GPS entries
    $tiff .= $be16(0x0001).$be16(2).$be32(2)."N\0\0";                        // GPSLatitudeRef
    $tiff .= $be32(0);

    $payload = "Exif\0\0".$tiff;
    $segment = "\xFF\xE1".pack('n', strlen($payload) + 2).$payload;

    $path = tempnam(sys_get_temp_dir(), 'mediaexif').'.jpg';
    file_put_contents($path, "\xFF\xD8".$segment.substr($base, 2));

    return new UploadedFile($path, $name, 'image/jpeg', null, true);
}

/**
 * A file that is not an image at all, whatever it is called.
 */
function textUpload(string $contents, string $name): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'mediax');

    file_put_contents($path, $contents);

    return new UploadedFile($path, $name, 'image/jpeg', null, true);
}

/**
 * Store an upload as a media row, the way the controller does.
 *
 * @param  array<string, mixed>  $attributes
 */
function storeMedia(UploadedFile $upload, array $attributes = []): Media
{
    $path = $upload->storeAs(
        (string) config('media.paths.original'),
        Str::uuid()->toString().'.jpg',
        (string) config('media.original_disk'),
    );

    return Media::factory()->create([
        'disk' => (string) config('media.original_disk'),
        'path' => $path,
        'original_name' => $upload->getClientOriginalName(),
        'mime_type' => 'image/jpeg',
        'file_size' => $upload->getSize(),
        ...$attributes,
    ]);
}

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

/*
|--------------------------------------------------------------------------
| T19 — MIME validation
|--------------------------------------------------------------------------
*/

test('a script renamed to a jpg is rejected', function () {
    // XC-M3: the extension is not evidence. The upload rule reads the bytes, so
    // this is text and gets refused.
    $upload = textUpload('<?php echo "hai"; ?>', 'foto.jpg');

    expect($upload->getMimeType())->not->toBe('image/jpeg');

    $this->actingAs(superAdmin())
        ->post(route('media.store'), ['file' => $upload])
        ->assertSessionHasErrors('file');

    expect(Media::query()->count())->toBe(0);
});

test('an svg is rejected', function () {
    $upload = textUpload('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>', 'logo.svg');

    $this->actingAs(superAdmin())
        ->post(route('media.store'), ['file' => $upload])
        ->assertSessionHasErrors('file');
});

test('a real jpeg is accepted', function () {
    $this->actingAs(superAdmin())
        ->post(route('media.store'), ['file' => jpegUpload()])
        ->assertSessionHasNoErrors();

    expect(Media::query()->count())->toBe(1);
});

test('the stored extension comes from the detected type, not the client name', function () {
    $upload = textUpload('<?php echo 1; ?>', 'evil.php');

    $this->actingAs(superAdmin())
        ->post(route('media.store'), ['file' => $upload])
        ->assertSessionHasErrors('file');

    // And the accepted case keeps the original name for display while the path is
    // built from the bytes.
    $this->actingAs(superAdmin())
        ->post(route('media.store'), ['file' => jpegUpload(1200, 600, 'nama asli.jpg')])
        ->assertSessionHasNoErrors();

    $media = Media::query()->firstOrFail();

    expect($media->original_name)->toBe('nama asli.jpg')
        ->and($media->path)->toEndWith('.jpg');
});

/*
|--------------------------------------------------------------------------
| T20 — size and dimensions
|--------------------------------------------------------------------------
*/

test('a file over the kilobyte ceiling is rejected', function () {
    config(['media.max_upload_kilobytes' => 1]);

    $this->actingAs(superAdmin())
        ->post(route('media.store'), ['file' => jpegUpload(900, 900)])
        ->assertSessionHasErrors('file');
});

test('an image with too many pixels is rejected before it is decoded', function () {
    // A 9x1 image is tiny on disk, so the size rule passes and only the dimension
    // rule can stop it.
    config(['media.max_width' => 8]);

    $this->actingAs(superAdmin())
        ->post(route('media.store'), ['file' => jpegUpload(9, 1)])
        ->assertSessionHasErrors('file');
});

/*
|--------------------------------------------------------------------------
| T21 — the processing job
|--------------------------------------------------------------------------
*/

test('an upload dispatches the processing job', function () {
    Queue::fake();

    $this->actingAs(superAdmin())
        ->post(route('media.store'), ['file' => jpegUpload()])
        ->assertSessionHasNoErrors();

    $media = Media::query()->firstOrFail();

    Queue::assertPushed(GenerateImageVariants::class, fn ($job): bool => $job->mediaId === $media->getKey());

    // The row exists before the job runs, and says it is waiting.
    expect($media->status)->toBe(MediaStatus::Pending)
        ->and($media->width)->toBeNull();
});

test('the job writes the variants and marks the media ready', function () {
    $media = storeMedia(jpegUpload());

    (new GenerateImageVariants($media->getKey()))->handle(app(ImageProcessor::class));

    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Ready)
        ->and($media->width)->toBe(1200)
        ->and($media->height)->toBe(600)
        ->and($media->variants()->count())->toBe(count((array) config('media.variants')));
});

test('running the job twice does not duplicate anything', function () {
    $media = storeMedia(jpegUpload());

    $processor = app(ImageProcessor::class);

    (new GenerateImageVariants($media->getKey()))->handle($processor);
    (new GenerateImageVariants($media->getKey()))->handle($processor);

    expect($media->refresh()->variants()->count())->toBe(count((array) config('media.variants')));
});

test('a failure marks the row failed and keeps the original', function () {
    $media = storeMedia(jpegUpload());

    // Point the row at a file that is not there, which is what a missing or
    // half-written upload looks like to the worker.
    $media->forceFill(['path' => 'media/originals/tidak-ada.jpg'])->save();

    (new GenerateImageVariants($media->getKey()))->failed(new RuntimeException('Gambar hilang.'));

    $media->refresh();

    expect($media->status)->toBe(MediaStatus::Failed)
        ->and($media->error_message)->toBe('Gambar hilang.');

    // Section 21: no database damage, and the original is kept so a retry by hand
    // is still possible.
    expect($media->exists)->toBeTrue();
});

test('a job for a deleted row does not explode', function () {
    $media = storeMedia(jpegUpload());
    $id = $media->getKey();
    $media->delete();

    (new GenerateImageVariants($id))->handle(app(ImageProcessor::class));

    expect(Media::query()->whereKey($id)->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| T22 — WebP output
|--------------------------------------------------------------------------
*/

test('every variant exists on the public disk as a real webp', function () {
    $media = storeMedia(jpegUpload());

    (new GenerateImageVariants($media->getKey()))->handle(app(ImageProcessor::class));

    $disk = Storage::disk((string) config('media.variant_disk'));

    foreach ($media->refresh()->variants as $variant) {
        expect($disk->exists($variant->path))->toBeTrue("variant {$variant->name} hilang")
            ->and($variant->mime_type)->toBe('image/webp')
            ->and($variant->path)->toEndWith('.webp');

        // Not just an extension: the bytes have to be WebP.
        expect($disk->mimeType($variant->path))->toBe('image/webp');
    }
});

test('a variant is never larger than the original width', function () {
    $media = storeMedia(jpegUpload(1200, 600));

    (new GenerateImageVariants($media->getKey()))->handle(app(ImageProcessor::class));

    foreach ($media->refresh()->variants as $variant) {
        expect($variant->width)->toBeLessThanOrEqual(1200)
            ->and($variant->height)->toBeLessThanOrEqual(600);
    }

    // The largest configured variant is 1600, so all three collapse onto the
    // original size here rather than being stretched.
    $thumb = $media->variants->firstWhere('name', 'thumb');

    expect($thumb->width)->toBe(400)
        ->and($thumb->height)->toBe(200);
});

test('the original stays on the private disk and has no public url', function () {
    $media = storeMedia(jpegUpload());

    (new GenerateImageVariants($media->getKey()))->handle(app(ImageProcessor::class));

    expect($media->refresh()->disk)->toBe(config('media.original_disk'))
        ->and($media->url())->toBeNull()
        ->and($media->url('large'))->not->toBeNull();
});

/*
|--------------------------------------------------------------------------
| T23 — EXIF
|--------------------------------------------------------------------------
*/

test('the fixture really does carry exif including gps', function () {
    $upload = jpegUploadWithExif();

    expect($upload->getRealPath())->not->toBeFalse();

    // Read with warnings suppressed: the fixture is deliberately minimal and
    // PHP is strict about a hand-built IFD.
    $exif = @exif_read_data($upload->getRealPath());

    expect($exif)->toBeArray()
        ->and($exif['Orientation'] ?? null)->toBe(6)
        ->and($exif['GPSLatitudeRef'] ?? null)->toBe('N');
});

test('published variants contain no exif at all', function () {
    $media = storeMedia(jpegUploadWithExif());

    (new GenerateImageVariants($media->getKey()))->handle(app(ImageProcessor::class));

    $disk = Storage::disk((string) config('media.variant_disk'));

    foreach ($media->refresh()->variants as $variant) {
        $contents = $disk->get($variant->path);

        // XC-M2: no EXIF, GPS or otherwise. GD writes no EXIF block, so there is
        // no separate stripping step that could be skipped.
        expect($contents)->not->toContain("Exif\0\0")
            ->and($variant->mime_type)->toBe('image/webp');
    }

    // And the produced WebP genuinely has no readable EXIF tags.
    $sample = $media->variants->firstWhere('name', 'large');
    $path = tempnam(sys_get_temp_dir(), 'variant').'.webp';
    file_put_contents($path, $disk->get($sample->path));

    $exif = @exif_read_data($path);

    expect($exif['Orientation'] ?? null)->toBeNull()
        ->and($exif['GPSLatitudeRef'] ?? null)->toBeNull();
});

test('a sideways photo is stored upright', function () {
    // Orientation 6 means the pixels need rotating 270 degrees to look right.
    $media = storeMedia(jpegUploadWithExif(orientation: 6));

    (new GenerateImageVariants($media->getKey()))->handle(app(ImageProcessor::class));

    $media->refresh();

    $thumb = $media->variants->firstWhere('name', 'thumb');

    // The fixture is 40 wide by 20 tall. After rotating 270 it is 20 by 40, so the
    // 400px thumb is capped at the new width of 20 and comes out as 20x40.
    expect($thumb->width)->toBe(20)
        ->and($thumb->height)->toBe(40);
});

/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

test('a guest cannot upload', function () {
    $this->post(route('media.store'), ['file' => jpegUpload()])
        ->assertRedirect(route('login'));
});

test('a user without media.create is refused', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->post(route('media.store'), ['file' => jpegUpload()])
        ->assertForbidden();

    expect(Media::query()->count())->toBe(0);
});

test('a user without media.delete cannot remove an upload', function () {
    seedRolesAndPermissions();
    $media = storeMedia(jpegUpload());

    $this->actingAs(User::factory()->create())
        ->delete(route('media.destroy', $media))
        ->assertForbidden();

    expect(Media::query()->count())->toBe(1);
});

/*
|--------------------------------------------------------------------------
| The status endpoint and deletion
|--------------------------------------------------------------------------
*/

test('the status endpoint reports the processing state as json', function () {
    $media = storeMedia(jpegUpload());

    $this->actingAs(superAdmin())
        ->getJson(route('media.show', $media))
        ->assertOk()
        ->assertJsonPath('media.id', $media->getKey())
        ->assertJsonPath('media.status', 'pending')
        ->assertJsonPath('media.variants', []);
});

test('the status endpoint needs media.view', function () {
    seedRolesAndPermissions();
    $media = storeMedia(jpegUpload());

    $this->actingAs(User::factory()->create())
        ->getJson(route('media.show', $media))
        ->assertForbidden();
});

test('deleting an upload removes its files and its row', function () {
    $media = storeMedia(jpegUpload());

    (new GenerateImageVariants($media->getKey()))->handle(app(ImageProcessor::class));

    $originalPath = $media->path;
    $variantPaths = $media->refresh()->variants->pluck('path')->all();

    $this->actingAs(superAdmin())
        ->delete(route('media.destroy', $media))
        ->assertRedirect();

    expect(Media::query()->count())->toBe(0)
        ->and(MediaVariant::query()->count())->toBe(0)
        ->and(Storage::disk('local')->exists($originalPath))->toBeFalse();

    foreach ($variantPaths as $path) {
        expect(Storage::disk('public')->exists($path))->toBeFalse("variant {$path} tertinggal");
    }
});
