<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;
use ZipArchive;

/**
 * Validates a document upload against config/documents.php.
 *
 * The extension is not evidence. The MIME type is read from the bytes, and for
 * OOXML the ZIP members are checked too, because finfo reports every DOCX as
 * application/zip and a plain ZIP renamed to .docx would otherwise pass.
 */
class AllowedDocumentFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile || ! $value->isValid()) {
            $fail('Berkas dokumen tidak valid.');

            return;
        }

        $extension = strtolower($value->getClientOriginalExtension());
        $detected = $value->getMimeType();

        if ($extension === '' || $detected === null) {
            $fail('Jenis berkas tidak dapat dikenali.');

            return;
        }

        /** @var array<string, string> $extensions */
        $extensions = (array) config('documents.extensions');

        /** @var array<string, array{mime: string, entries: list<string>}> $ooxml */
        $ooxml = (array) config('documents.ooxml');

        /** @var array<string, array{mime: string, mimes: list<string>}> $legacyOffice */
        $legacyOffice = (array) config('documents.legacy_office');

        if (array_key_exists($extension, $legacyOffice)) {
            if (! in_array($detected, $legacyOffice[$extension]['mimes'], true)) {
                $fail('Isi berkas tidak cocok dengan ekstensi .'.$extension.'.');
            }

            return;
        }

        if (array_key_exists($extension, $ooxml)) {
            $this->validateOoxml($value, $extension, $ooxml[$extension], $fail);

            return;
        }

        if (in_array($detected, ['application/zip', 'application/x-zip-compressed'], true)) {
            if ($extension !== 'zip') {
                $fail('Berkas ZIP harus memakai ekstensi .zip.');

                return;
            }

            return;
        }

        if (! in_array($detected, (array) config('documents.mime_types'), true)) {
            $fail('Jenis berkas tidak diizinkan. Gunakan PDF, DOC/DOCX, XLS/XLSX, PPT/PPTX, atau ZIP.');

            return;
        }

        $canonicalExtension = $extensions[$detected] ?? null;

        if ($canonicalExtension === null || $extension !== $canonicalExtension) {
            $fail('Ekstensi berkas tidak cocok dengan jenis berkas yang terdeteksi.');
        }
    }

    /**
     * @param  array{mime: string, entries: list<string>}  $signature
     */
    private function validateOoxml(
        UploadedFile $file,
        string $extension,
        array $signature,
        Closure $fail,
    ): void {
        if (! class_exists(ZipArchive::class)) {
            $fail('Server belum mendukung validasi berkas Office.');

            return;
        }

        $archive = new ZipArchive;
        $path = $file->getRealPath();

        if ($path === false || @$archive->open($path) !== true) {
            $fail('Berkas Office tidak dapat dibaca.');

            return;
        }

        try {
            foreach ($signature['entries'] as $entry) {
                if ($archive->locateName($entry) === false) {
                    $fail('Isi berkas tidak cocok dengan ekstensi .'.$extension.'.');

                    return;
                }
            }
        } finally {
            $archive->close();
        }
    }
}
