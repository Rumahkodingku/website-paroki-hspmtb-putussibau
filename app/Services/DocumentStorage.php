<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * The document file lifecycle on the private disk.
 *
 * Nothing here reads the HTTP request. The path is always generated from the
 * detected type; the client's filename is kept only as display metadata.
 */
class DocumentStorage
{
    /**
     * @return array{file_disk: string, file_path: string, original_filename: string, mime_type: string, file_size: int}
     */
    public function store(UploadedFile $file): array
    {
        $mimeType = $this->canonicalMime($file);
        $extension = $this->extensionFor($mimeType);
        $disk = (string) config('documents.disk');
        $directory = trim((string) config('documents.path'), '/');
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs($directory, $filename, $disk);

        if (! is_string($path)) {
            throw new RuntimeException('Berkas dokumen gagal disimpan.');
        }

        return [
            'file_disk' => $disk,
            'file_path' => $path,
            'original_filename' => $this->safeOriginalName($file),
            'mime_type' => $mimeType,
            'file_size' => (int) $file->getSize(),
        ];
    }

    public function delete(string $disk, string $path): void
    {
        $deleted = Storage::disk($disk)->delete($path);

        if (! $deleted) {
            Log::warning('Berkas dokumen gagal dihapus.', [
                'disk' => $disk,
                'path' => $path,
            ]);
        }
    }

    public function exists(Document $document): bool
    {
        return Storage::disk($document->file_disk)->exists($document->file_path);
    }

    public function extensionFor(string $mimeType): string
    {
        /** @var array<string, string> $extensions */
        $extensions = (array) config('documents.extensions');

        return $extensions[$mimeType] ?? 'bin';
    }

    public function downloadFilename(Document $document): string
    {
        $name = trim(Str::slug($document->title));

        if ($name === '') {
            $name = 'dokumen-'.$document->getKey();
        }

        return $name.'.'.$document->extension();
    }

    /**
     * OOXML arrives as application/zip; store the format the extension names.
     */
    private function canonicalMime(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        /** @var array<string, array{mime: string, entries: list<string>}> $ooxml */
        $ooxml = (array) config('documents.ooxml');

        if (isset($ooxml[$extension])) {
            return $ooxml[$extension]['mime'];
        }

        /** @var array<string, array{mime: string, mimes: list<string>}> $legacyOffice */
        $legacyOffice = (array) config('documents.legacy_office');

        if (isset($legacyOffice[$extension])) {
            return $legacyOffice[$extension]['mime'];
        }

        return (string) $file->getMimeType();
    }

    private function safeOriginalName(UploadedFile $file): string
    {
        $name = str_replace('\\', '/', $file->getClientOriginalName());
        $name = basename($name);
        $name = (string) preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
        $name = trim($name);

        if ($name === '') {
            $name = 'dokumen';
        }

        return Str::limit($name, (int) config('documents.max_filename_length'), '');
    }
}
