<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function pdfUpload(string $name = 'dokumen.pdf', int $kilobytes = 10): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'doc').'.pdf';
    $content = "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
    $content = str_pad($content, max(strlen($content), $kilobytes * 1024), ' ');

    file_put_contents($path, $content);

    return new UploadedFile($path, $name, 'application/pdf', null, true);
}

function disguisedPhpUpload(string $name = 'dokumen.pdf'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'docx');

    file_put_contents($path, '<?php echo "x"; ?>');

    return new UploadedFile($path, $name, 'application/pdf', null, true);
}

function oleUpload(string $extension = 'doc', ?string $name = null): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'ole').'.'.$extension;
    $content = hex2bin('D0CF11E0A1B11AE1').str_repeat("\0", 504);

    file_put_contents($path, $content);

    $mime = match ($extension) {
        'xls' => 'application/vnd.ms-excel',
        'ppt' => 'application/vnd.ms-powerpoint',
        default => 'application/msword',
    };

    return new UploadedFile($path, $name ?? 'dokumen.'.$extension, $mime, null, true);
}

function ooxmlUpload(string $extension = 'docx', bool $valid = true): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'docx').'.'.$extension;
    $archive = new ZipArchive;
    $archive->open($path, ZipArchive::CREATE);

    if ($valid) {
        $archive->addFromString('[Content_Types].xml', '<Types/>');

        $entry = match ($extension) {
            'xlsx' => 'xl/workbook.xml',
            'pptx' => 'ppt/presentation.xml',
            default => 'word/document.xml',
        };

        $archive->addFromString($entry, '<root/>');
    } else {
        $archive->addFromString('catatan.txt', 'bukan dokumen office');
    }

    $archive->close();

    $mime = match ($extension) {
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        default => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    };

    return new UploadedFile($path, 'dokumen.'.$extension, $mime, null, true);
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function documentPayload(array $overrides = []): array
{
    $categoryId = $overrides['document_category_id']
        ?? DocumentCategory::factory()->create()->getKey();

    return array_merge([
        'title' => 'Pedoman Pelayanan',
        'document_category_id' => $categoryId,
        'description' => 'Panduan singkat pelayanan paroki.',
        'version_label' => '1.0',
        'document_date' => '2026-10-01',
        'expires_at' => null,
        'status' => 'draft',
        'is_featured' => false,
        'file' => pdfUpload(),
    ], $overrides);
}

function putDocumentFile(Document $document, string $content = '%PDF-1.4\n%%EOF\n'): void
{
    Storage::disk($document->file_disk)->put($document->file_path, $content);
}
