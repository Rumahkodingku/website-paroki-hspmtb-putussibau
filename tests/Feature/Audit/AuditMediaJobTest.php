<?php

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\AuditEvent;
use App\Models\Media;
use App\Services\ImageProcessor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');
});

function auditImageMedia(): Media
{
    $image = imagecreatetruecolor(120, 80);
    imagefilledrectangle($image, 0, 0, 120, 80, truecolor($image, 20, 80, 160));

    $path = tempnam(sys_get_temp_dir(), 'audit-media').'.jpg';
    imagejpeg($image, $path, 90);
    imagedestroy($image);

    $stored = Str::uuid()->toString().'.jpg';

    Storage::disk((string) config('media.original_disk'))->put(
        'media/originals/'.$stored,
        (string) file_get_contents($path),
    );

    return Media::factory()->create([
        'disk' => (string) config('media.original_disk'),
        'path' => 'media/originals/'.$stored,
        'original_name' => 'audit.jpg',
        'mime_type' => 'image/jpeg',
        'status' => MediaStatus::Pending,
    ]);
}

it('records an accepted upload without claiming processing finished', function () {
    Queue::fake();

    $this->actingAs(superAdmin())
        ->post(route('media.store'), [
            'file' => UploadedFile::fake()->image('audit.jpg', 100, 100),
        ])
        ->assertSessionHasNoErrors();

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->module)->toBe('media')
        ->and($event->action)->toBe('upload_accepted')
        ->and($event->result)->toBe('success')
        ->and($event->metadata)->toHaveKey('original_name', 'audit.jpg');
});

it('records a completed job as a system event', function () {
    $media = auditImageMedia();

    (new GenerateImageVariants($media->getKey()))->handle(app(ImageProcessor::class));

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->action)->toBe('media_processing_completed')
        ->and($event->actor_type)->toBe('system')
        ->and($event->metadata['media_id'])->toBe($media->getKey())
        ->and($event->metadata['variants'])->not->toBeEmpty();
});

it('records a failed job as a system event without the exception message', function () {
    $media = auditImageMedia();

    (new GenerateImageVariants($media->getKey()))->failed(new RuntimeException('path rahasia'));

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->action)->toBe('media_processing_failed')
        ->and($event->result)->toBe('failure')
        ->and($event->actor_type)->toBe('system')
        ->and($event->metadata['error_class'])->toBe(RuntimeException::class)
        ->and(json_encode($event->metadata))->not->toContain('path rahasia');
});
