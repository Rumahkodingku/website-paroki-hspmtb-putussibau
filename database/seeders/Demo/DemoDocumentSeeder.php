<?php

namespace Database\Seeders\Demo;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;
use Database\Seeders\DocumentCategorySeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Seeds one published demo document from the supplied PDF.
 *
 * The PDF is copied into the private document disk, never served from
 * public/ directly. Downloads keep going through PublicDocumentController,
 * which checks the published flag and streams the file.
 *
 * Idempotent: the row is keyed by its stable private path.
 *
 * Demo-only: called by DemoContentSeeder when config('demo.enabled') is true.
 */
class DemoDocumentSeeder extends Seeder
{
    private const SOURCE = 'illustrations/demo/documents/test-dokumen.pdf';

    private const PATH = 'documents/demo/test-dokumen.pdf';

    public function run(): void
    {
        $this->call(DocumentCategorySeeder::class);

        $source = public_path(self::SOURCE);

        if (! File::exists($source)) {
            throw new RuntimeException('Berkas '.self::SOURCE.' tidak ditemukan di folder public/.');
        }

        $category = DocumentCategory::query()->where('slug', 'lainnya')->firstOrFail();
        $disk = (string) config('documents.disk');
        $authorId = User::query()
            ->where('email', 'redaksi-demo@example.test')
            ->value('id');

        Storage::disk($disk)->makeDirectory(dirname(self::PATH));
        Storage::disk($disk)->put(self::PATH, (string) File::get($source));

        $document = Document::withTrashed()->updateOrCreate(
            ['file_path' => self::PATH],
            [
                'document_category_id' => $category->getKey(),
                'title' => 'Dokumen Demo Paroki',
                'description' => 'Contoh dokumen PDF untuk pratinjau halaman unduhan paroki.',
                'file_disk' => $disk,
                'original_filename' => 'test-dokumen.pdf',
                'mime_type' => 'application/pdf',
                'file_size' => File::size($source),
                'version_label' => 'Demo',
                'document_date' => Carbon::today()->toDateString(),
                'expires_at' => null,
                'status' => DocumentStatus::Published,
                'is_featured' => true,
                'published_at' => now(),
                'uploaded_by' => $authorId,
                'updated_by' => $authorId,
            ],
        );

        if ($document->trashed()) {
            $document->restore();
        }
    }
}
