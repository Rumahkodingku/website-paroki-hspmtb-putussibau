import { execFileSync } from 'node:child_process';
import { e2eEnvironment } from './environment';

/*
 * Seeds one published album with two ready photos, for the specs that need a
 * gallery to look at.
 *
 * Why this exists: uploads go through the queue (QUEUE_CONNECTION=database) and
 * no worker runs under `php artisan serve`, so a file uploaded in the browser
 * never finishes processing. The editor spec notes the same limitation. A photo
 * that is actually `ready`, with its variants, therefore has to be planted
 * directly — otherwise there is nothing to open a lightbox over.
 *
 * The media and variant rows are made through the real factories, and the album
 * is force-deleted first so calling this more than once in a run is safe despite
 * the unique slug.
 */

const SCRIPT = String.raw`
$album = \App\Models\GalleryAlbum::withTrashed()->where('slug', 'album-e2e')->first();
if ($album !== null) {
    $album->forceDelete();
}
$media = [];
foreach (range(1, 2) as $index) {
    $media[] = \App\Models\Media::factory()->ready()->create();
}
$album = \App\Models\GalleryAlbum::factory()->published()->create([
    'title' => 'Album E2E',
    'slug' => 'album-e2e',
    'event_date' => '2025-06-01',
]);
foreach ($media as $index => $item) {
    foreach (['thumb', 'medium', 'large'] as $name) {
        \App\Models\MediaVariant::factory()->create([
            'media_id' => $item->getKey(),
            'name' => $name,
            'disk' => 'public',
            'path' => 'media/variants/'.$item->getKey().'/'.$name.'.webp',
        ]);
    }
    \App\Models\GalleryPhoto::factory()->create([
        'gallery_album_id' => $album->getKey(),
        'media_id' => $item->getKey(),
        'sort_order' => $index,
        'caption' => $index === 0 ? 'Foto Pertama' : 'Foto Kedua',
    ]);
}
`;

export function seedGalleryAlbum(): void {
    const environment = e2eEnvironment();

    execFileSync('php', ['artisan', 'tinker', '--execute', SCRIPT], {
        env: { ...process.env, DB_DATABASE: environment.database },
        stdio: 'ignore',
    });
}
