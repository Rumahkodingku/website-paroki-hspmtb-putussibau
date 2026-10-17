import { execFileSync } from 'node:child_process';
import { e2eEnvironment } from './environment';

/*
 * Plants one published document and one active service for the integration
 * spec. The document file is written to the private disk directly because this
 * spec is about the service link, not about the upload form (the main document
 * spec already exercises the upload).
 */

const SCRIPT = String.raw`
$oldService = \App\Models\Service::withTrashed()->where('slug', 'layanan-e2e-dokumen')->first();
$oldDocument = \App\Models\Document::withTrashed()->where('title', 'Dokumen E2E Layanan')->first();

if ($oldService !== null) {
    $oldService->documents()->detach();
    $oldService->forceDelete();
}

if ($oldDocument !== null) {
    $oldDocument->services()->detach();
    $oldDocument->forceDelete();
}

$document = \App\Models\Document::factory()->published()->create([
    'title' => 'Dokumen E2E Layanan',
    'original_filename' => 'dokumen-e2e-layanan.pdf',
    'file_path' => 'documents/dokumen-e2e-layanan.pdf',
]);

\Illuminate\Support\Facades\Storage::disk($document->file_disk)->put(
    $document->file_path,
    "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n",
);

\App\Models\Service::factory()->active()->create([
    'title' => 'Layanan E2E Dokumen',
    'slug' => 'layanan-e2e-dokumen',
]);
`;

export function seedDocumentServiceLink(): void {
    const environment = e2eEnvironment();

    execFileSync('php', ['artisan', 'tinker', '--execute', SCRIPT], {
        env: { ...process.env, DB_DATABASE: environment.database },
        stdio: 'ignore',
    });
}
