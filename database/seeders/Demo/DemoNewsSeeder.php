<?php

namespace Database\Seeders\Demo;

use App\Enums\MediaStatus;
use App\Enums\PostStatus;
use App\Jobs\GenerateImageVariants;
use App\Models\Media;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Seeds 50 demo news posts.
 *
 * Demo-only: called by DemoContentSeeder when config('demo.enabled') is true.
 */
class DemoNewsSeeder extends Seeder
{
    private const IMAGE = 'illustrations/demo/thumbnail-16-9.png';

    public function run(): void
    {
        $author = User::query()->where('email', 'redaksi-demo@example.test')->firstOrFail();
        $media = $this->dummyMedia();
        $imagePath = $media->variantPath($this->largestVariantName());

        $categories = PostCategory::query()->pluck('id', 'slug');

        // Clean up the five posts the previous version of this seeder created,
        // so the total stays at 50 instead of 5 + 50.
        Post::query()->whereIn('slug', [
            'misa-syukur-pesta-pelindung-paroki',
            'kegiatan-bakti-sosial-lingkungan',
            'renungan-pekan-biasa',
            'pengumuman-jadwal-misa-harian',
            'pendaftaran-katekese-anak',
        ])->forceDelete();
        Post::query()->where('slug', 'like', 'demo-berita-%')->forceDelete();

        fake()->seed(20250309);

        $categorySlugs = ['berita', 'kegiatan', 'renungan', 'pengumuman'];
        $scriptures = ['Yohanes 15:13', 'Matius 5:14', 'Roma 8:28', 'Mazmur 23:1', 'Filipi 4:13'];

        for ($i = 0; $i < 50; $i++) {
            $categorySlug = $categorySlugs[$i % count($categorySlugs)];
            $categoryId = $categories[$categorySlug] ?? null;

            if ($categoryId === null) {
                continue;
            }

            $title = (string) Str::title(fake()->unique()->sentence(4));
            $slug = 'demo-berita-'.$i;

            $paragraphs = fake()->paragraphs(random_int(6, 8));
            if (is_string($paragraphs)) {
                $paragraphs = [$paragraphs];
            }

            $paragraphHtml = implode('', array_map(
                static fn (string $paragraph): string => '<p>'.$paragraph.'</p>',
                $paragraphs
            ));

            $words = fake()->words(4);
            if (is_string($words)) {
                $words = [$words];
            }

            $listItems = implode('', array_map(
                static fn (string $item): string => '<li>'.$item.'</li>',
                $words
            ));

            $content = '<h2>'.fake()->sentence(4).'</h2>'
                .$paragraphHtml
                .'<ul>'.$listItems.'</ul>'
                .'<p>'.fake()->paragraph(3).'</p>';

            Post::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'post_category_id' => $categoryId,
                    'author_id' => $author->getKey(),
                    'author_name' => null,
                    'title' => $title,
                    'excerpt' => fake()->sentence(12),
                    'content' => $content,
                    'featured_image' => $imagePath,
                    'image_alt' => 'Ilustrasi kegiatan Paroki',
                    'scripture_reference' => $categorySlug === 'renungan'
                        ? $scriptures[array_rand($scriptures)]
                        : null,
                    'status' => PostStatus::Published,
                    'published_at' => Carbon::now()->subDays(random_int(0, 60))->utc(),
                    'is_pinned' => $i < 5,
                    'view_count' => random_int(20, 400),
                    'meta_title' => null,
                    'meta_description' => null,
                    'media_id' => $media->getKey(),
                ],
            );
        }
    }

    private function dummyMedia(): Media
    {
        $source = public_path(self::IMAGE);

        if (! File::exists($source)) {
            throw new \RuntimeException('Berkas '.self::IMAGE.' tidak ditemukan di folder public/.');
        }

        $disk = (string) config('media.original_disk');
        $path = 'media/originals/dummy-berita.png';

        Storage::disk($disk)->makeDirectory(dirname($path));
        Storage::disk($disk)->put($path, (string) File::get($source));

        $media = Media::query()->updateOrCreate(
            ['path' => $path],
            [
                'disk' => $disk,
                'original_name' => 'thumbnail-16-9.png',
                'mime_type' => 'image/png',
                'file_size' => File::size($source),
                'width' => null,
                'height' => null,
                'alt_text' => 'Gambar contoh untuk pratinjau antarmuka.',
                'status' => MediaStatus::Pending,
                'uploader_id' => null,
                'error_message' => null,
            ],
        );

        GenerateImageVariants::dispatchSync($media->getKey());

        return $media->fresh()->load('variants');
    }

    private function largestVariantName(): string
    {
        /** @var array<string, int> $variants */
        $variants = (array) config('media.variants');

        return (string) collect($variants)->sortDesc()->keys()->first();
    }
}
