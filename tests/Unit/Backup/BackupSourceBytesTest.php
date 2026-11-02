<?php

use App\Services\Backup\BackupCreator;
use Illuminate\Support\Facades\Storage;

it('sums the files a full backup will copy', function () {
    Storage::fake('local');
    Storage::fake('public');

    config([
        'media.original_disk' => 'local',
        'media.paths.original' => 'media/originals',
        'media.variant_disk' => 'public',
        'media.paths.variants' => 'media/variants',
        'documents.disk' => 'local',
        'documents.path' => 'documents',
    ]);

    Storage::disk('local')->put('media/originals/a.jpg', str_repeat('a', 10));
    Storage::disk('local')->put('media/originals/2026/b.jpg', str_repeat('b', 5));
    Storage::disk('local')->put('media/originals/.gitkeep', str_repeat('x', 99));
    Storage::disk('public')->put('media/variants/c.webp', str_repeat('c', 20));
    Storage::disk('local')->put('documents/d.pdf', str_repeat('d', 30));

    expect(app(BackupCreator::class)->sourceBytes())->toBe(65);
});
