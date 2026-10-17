<?php

require_once __DIR__.'/Support.php';

use App\Models\Document;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

it('serves a published document as a safe attachment', function () {
    $document = Document::factory()->published()->create([
        'title' => 'Pedoman "Pelayanan" / Paroki',
    ]);
    putDocumentFile($document, '%PDF-1.4 isi dokumen');

    $response = $this->get(route('dokumen.unduh', $document));

    $response->assertOk()
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Content-Type', 'application/pdf');

    expect((string) $response->headers->get('Content-Disposition'))
        ->toContain('attachment')
        ->toContain('pedoman-pelayanan-paroki.pdf')
        ->and($response->streamedContent())->toContain('%PDF-1.4');
});

it('serves office files with the canonical content type', function () {
    $document = Document::factory()->published()->create([
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'file_path' => 'documents/pedoman.docx',
    ]);
    putDocumentFile($document, 'PK isi docx');

    $this->get(route('dokumen.unduh', $document))
        ->assertOk()
        ->assertHeader(
            'Content-Type',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        );
});

it('refuses a published row with no publication date', function () {
    $document = Document::factory()->create([
        'status' => 'published',
        'published_at' => null,
    ]);
    putDocumentFile($document);

    $this->get(route('dokumen.unduh', $document))->assertNotFound();
});

it('increments download_count on every attachment request', function () {
    $document = Document::factory()->published()->create();
    putDocumentFile($document);

    $this->get(route('dokumen.unduh', $document))->assertOk();
    $this->get(route('dokumen.unduh', $document))->assertOk();

    expect($document->fresh()->download_count)->toBe(2);
});

it('serves pdf inline without incrementing the counter', function () {
    $document = Document::factory()->published()->create();
    putDocumentFile($document, '%PDF-1.4 inline');

    $response = $this->get(route('dokumen.unduh', ['document' => $document, 'inline' => 1]));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    expect((string) $response->headers->get('Content-Disposition'))
        ->toContain('inline')
        ->and($document->fresh()->download_count)->toBe(0);
});

it('refuses inline preview for a non pdf document', function () {
    $document = Document::factory()->published()->create([
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'file_path' => 'documents/dokumen.docx',
    ]);
    putDocumentFile($document);

    $this->get(route('dokumen.unduh', ['document' => $document, 'inline' => 1]))
        ->assertNotFound();
});

it('answers 404 for a draft, archived, or soft-deleted document', function () {
    $draft = Document::factory()->create();
    $archived = Document::factory()->archived()->create();
    $deleted = Document::factory()->published()->create();
    putDocumentFile($draft);
    putDocumentFile($archived);
    putDocumentFile($deleted);
    $deleted->delete();

    $this->get(route('dokumen.unduh', $draft))->assertNotFound();
    $this->get(route('dokumen.unduh', $archived))->assertNotFound();
    $this->get(route('dokumen.unduh', $deleted))->assertNotFound();
});

it('answers 404 safely when the file is missing', function () {
    $document = Document::factory()->published()->create();

    $this->get(route('dokumen.unduh', $document))->assertNotFound();
});

it('never accepts an arbitrary file path', function () {
    $document = Document::factory()->published()->create();
    putDocumentFile($document);

    $this->get(route('dokumen.unduh', $document).'?path=/etc/passwd')
        ->assertOk();

    expect($document->fresh()->download_count)->toBe(1);
});
