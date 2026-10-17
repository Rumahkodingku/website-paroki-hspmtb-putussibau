<?php

require_once __DIR__.'/Support.php';

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\Service;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

it('creates a document with a valid file', function () {
    $category = DocumentCategory::factory()->create();
    $admin = superAdmin();

    $this->actingAs($admin)
        ->post(route('documents.store'), documentPayload([
            'document_category_id' => $category->getKey(),
            'status' => 'published',
            'is_featured' => true,
        ]))
        ->assertRedirect();

    $document = Document::query()->sole();

    expect($document->title)->toBe('Pedoman Pelayanan')
        ->and($document->status)->toBe(DocumentStatus::Published)
        ->and($document->is_featured)->toBeTrue()
        ->and($document->published_at)->not->toBeNull()
        ->and($document->uploaded_by)->toBe($admin->getKey())
        ->and($document->mime_type)->toBe('application/pdf')
        ->and($document->file_size)->toBeGreaterThan(0)
        ->and($document->file_disk)->toBe('local');

    Storage::disk('local')->assertExists($document->file_path);
});

it('requires a file when creating a document', function () {
    $category = DocumentCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->post(route('documents.store'), documentPayload([
            'document_category_id' => $category->getKey(),
            'file' => null,
        ]))
        ->assertSessionHasErrors('file');

    expect(Document::count())->toBe(0);
});

it('rejects a php script renamed to pdf', function () {
    $this->actingAs(superAdmin())
        ->post(route('documents.store'), documentPayload([
            'file' => disguisedPhpUpload(),
        ]))
        ->assertSessionHasErrors('file');

    expect(Document::count())->toBe(0);
});

it('rejects a file above ten megabytes', function () {
    $this->actingAs(superAdmin())
        ->post(route('documents.store'), documentPayload([
            'file' => pdfUpload('besar.pdf', 10 * 1024 + 1),
        ]))
        ->assertSessionHasErrors('file');

    expect(Document::count())->toBe(0);
});

it('accepts a valid docx and stores the canonical mime type', function () {
    $this->actingAs(superAdmin())
        ->post(route('documents.store'), documentPayload([
            'file' => ooxmlUpload('docx'),
        ]))
        ->assertSessionHasNoErrors();

    expect(Document::query()->sole()->mime_type)
        ->toBe('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
});

it('accepts legacy office documents reported as ole storage', function (string $extension, string $mime) {
    $this->actingAs(superAdmin())
        ->post(route('documents.store'), documentPayload([
            'file' => oleUpload($extension),
        ]))
        ->assertSessionHasNoErrors();

    $document = Document::query()->sole();

    expect($document->mime_type)->toBe($mime)
        ->and($document->extension())->toBe($extension);
})->with([
    'doc' => ['doc', 'application/msword'],
    'xls' => ['xls', 'application/vnd.ms-excel'],
    'ppt' => ['ppt', 'application/vnd.ms-powerpoint'],
]);

it('rejects a plain zip renamed to docx', function () {
    $this->actingAs(superAdmin())
        ->post(route('documents.store'), documentPayload([
            'file' => ooxmlUpload('docx', false),
        ]))
        ->assertSessionHasErrors('file');
});

it('updates metadata without requiring or replacing the file', function () {
    $document = Document::factory()->create();
    putDocumentFile($document);
    $oldPath = $document->file_path;

    $this->actingAs(superAdmin())
        ->patch(route('documents.update', $document), documentPayload([
            'document_category_id' => $document->document_category_id,
            'title' => 'Judul Baru',
            'file' => null,
        ]))
        ->assertRedirect(route('documents.edit', $document));

    expect($document->fresh()->title)->toBe('Judul Baru')
        ->and($document->fresh()->file_path)->toBe($oldPath);

    Storage::disk('local')->assertExists($oldPath);
});

it('replaces a file and deletes the old one after success', function () {
    $document = Document::factory()->create();
    putDocumentFile($document, '%PDF-1.4 old');
    $oldPath = $document->file_path;

    $this->actingAs(superAdmin())
        ->patch(route('documents.update', $document), documentPayload([
            'document_category_id' => $document->document_category_id,
            'file' => pdfUpload('revisi.pdf', 20),
        ]))
        ->assertSessionHasNoErrors();

    $document->refresh();

    expect($document->file_path)->not->toBe($oldPath)
        ->and($document->original_filename)->toBe('revisi.pdf');

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($document->file_path);
});

it('deletes the file and detaches services on delete', function () {
    $document = Document::factory()->published()->create();
    putDocumentFile($document);
    $service = Service::factory()->create();
    $service->documents()->attach($document);

    $this->actingAs(superAdmin())
        ->delete(route('documents.destroy', $document))
        ->assertRedirect(route('documents.index'));

    expect(Document::query()->count())->toBe(0)
        ->and(Document::withTrashed()->count())->toBe(1)
        ->and($service->fresh()->documents()->count())->toBe(0);

    Storage::disk('local')->assertMissing($document->file_path);
});

it('keeps published_at after the first publication', function () {
    $document = Document::factory()->create();

    $this->actingAs(superAdmin())
        ->patch(route('documents.update', $document), documentPayload([
            'document_category_id' => $document->document_category_id,
            'status' => 'published',
            'file' => null,
        ]))
        ->assertSessionHasNoErrors();

    $first = $document->fresh()->published_at;

    $this->travel(1)->minutes();

    $this->actingAs(superAdmin())
        ->patch(route('documents.update', $document), documentPayload([
            'document_category_id' => $document->document_category_id,
            'status' => 'published',
            'file' => null,
        ]))
        ->assertSessionHasNoErrors();

    expect($document->fresh()->published_at->equalTo($first))->toBeTrue();
});

it('rejects an expired date before the document date', function () {
    $this->actingAs(superAdmin())
        ->post(route('documents.store'), documentPayload([
            'document_date' => '2026-10-10',
            'expires_at' => '2026-10-01',
        ]))
        ->assertSessionHasErrors('expires_at');
});

it('lists and filters documents in the admin', function () {
    $published = Document::factory()->published()->create(['title' => 'Pedoman']);
    Document::factory()->create(['title' => 'Draf Internal']);

    $this->actingAs(superAdmin())
        ->get(route('documents.index', ['status' => 'published']))
        ->assertInertia(fn ($page) => $page
            ->component('admin/dokumen')
            ->has('documents.data', 1)
            ->where('documents.data.0.id', $published->getKey()));

    $this->actingAs(superAdmin())
        ->get(route('documents.index', ['search' => 'Draf']))
        ->assertInertia(fn ($page) => $page->has('documents.data', 1));
});

it('ignores an unknown sort key', function () {
    Document::factory()->create();

    $this->actingAs(superAdmin())
        ->get(route('documents.index', ['sort' => 'deleted_at', 'direction' => 'desc']))
        ->assertOk();
});
