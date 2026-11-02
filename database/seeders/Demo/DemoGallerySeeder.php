<?php

namespace Database\Seeders\Demo;

use App\Enums\MediaStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SplFileInfo;

/**
 * Seeds five gallery albums from the supplied demo photographs.
 *
 * Every album uses the same ten Media rows. That is deliberate: the images are
 * processed once (three WebP variants each) and only the album-to-photo join
 * rows are repeated, so five albums do not mean thirty variants of the same
 * photograph.
 *
 * Idempotent: Media are keyed by their storage path, albums by slug, and the
 * photo rows of each demo album are rebuilt so a second run cannot accumulate
 * duplicates.
 *
 * Demo-only: called by DemoContentSeeder when config('demo.enabled') is true.
 */
class DemoGallerySeeder extends Seeder
{
    private const SOURCE_DIRECTORY = 'illustrations/demo/galleries';

    private const ALBUM_COUNT = 5;

    private const EXPECTED_IMAGES = 10;

    public function run(): void
    {
        $media = $this->demoMedia();
        $authorId = User::query()
            ->where('email', 'redaksi-demo@example.test')
            ->value('id');

        for ($index = 1; $index <= self::ALBUM_COUNT; $index++) {
            $album = GalleryAlbum::withTrashed()->updateOrCreate(
                ['slug' => 'demo-galeri-'.$index],
                [
                    'title' => 'Galeri Demo '.$index,
                    'description' => 'Album contoh untuk pratinjau halaman galeri paroki.',
                    'event_date' => Carbon::today()->subDays(($index - 1) * 7)->toDateString(),
                    'location' => 'Paroki HSPMTB',
                    'cover_photo_id' => null,
                    'is_published' => true,
                    'published_at' => Carbon::now()->subDays($index - 1),
                    'created_by' => $authorId,
                    'updated_by' => $authorId,
                ],
            );

            if ($album->trashed()) {
                $album->restore();
            }

            // The album keeps the same photos on every run; deleting the join
            // rows first is what makes the count deterministic.
            GalleryPhoto::query()
                ->where('gallery_album_id', $album->getKey())
                ->delete();

            $photos = [];

            foreach ($media->values() as $sortOrder => $item) {
                $photos[] = GalleryPhoto::query()->create([
                    'gallery_album_id' => $album->getKey(),
                    'media_id' => $item->getKey(),
                    'caption' => 'Foto demo '.($sortOrder + 1),
                    'alt_text' => 'Ilustrasi kegiatan paroki (demo)',
                    'sort_order' => $sortOrder,
                ]);
            }

            $album->forceFill(['cover_photo_id' => $photos[0]->getKey()])->save();
        }
    }

    /**
     * Copy the ten source images into the media pipeline and process them.
     *
     * @return Collection<int, Media>
     */
    private function demoMedia(): Collection
    {
        $directory = public_path(self::SOURCE_DIRECTORY);

        if (! File::isDirectory($directory)) {
            throw new RuntimeException('Folder '.self::SOURCE_DIRECTORY.' tidak ditemukan.');
        }

        $files = collect(File::files($directory))
            ->sortBy(fn (SplFileInfo $file): string => $file->getFilename(), SORT_NATURAL)
            ->values();

        if ($files->count() !== self::EXPECTED_IMAGES) {
            throw new RuntimeException(sprintf(
                'Folder %s harus berisi %d gambar, ditemukan %d.',
                self::SOURCE_DIRECTORY,
                self::EXPECTED_IMAGES,
                $files->count(),
            ));
        }

        $disk = (string) config('media.original_disk');

        return $files->map(function (SplFileInfo $file) use ($disk): Media {
            $path = 'media/originals/demo-galeri/'.$file->getFilename();

            Storage::disk($disk)->makeDirectory(dirname($path));
            Storage::disk($disk)->put($path, (string) File::get($file->getPathname()));

            $media = Media::query()->updateOrCreate(
                ['path' => $path],
                [
                    'disk' => $disk,
                    'original_name' => $file->getFilename(),
                    'mime_type' => (string) File::mimeType($file->getPathname()),
                    'file_size' => (int) $file->getSize(),
                    'width' => null,
                    'height' => null,
                    'alt_text' => 'Ilustrasi kegiatan paroki (demo)',
                    'status' => MediaStatus::Pending,
                    'uploader_id' => null,
                    'error_message' => null,
                ],
            );

            GenerateImageVariants::dispatchSync($media->getKey());

            return $media->refresh();
        });
    }
}
