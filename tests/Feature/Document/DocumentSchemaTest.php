<?php

require_once __DIR__.'/Support.php';

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentCategory;
use Illuminate\Support\Facades\Schema;

it('creates the document tables', function (string $table) {
    expect(Schema::hasTable($table))->toBeTrue();
})->with(['document_categories', 'documents', 'document_service']);

it('stores the document metadata and private file reference', function (string $column) {
    expect(Schema::hasColumn('documents', $column))->toBeTrue();
})->with([
    'document_category_id',
    'title',
    'description',
    'file_disk',
    'file_path',
    'original_filename',
    'mime_type',
    'file_size',
    'version_label',
    'document_date',
    'expires_at',
    'status',
    'is_featured',
    'download_count',
    'published_at',
    'uploaded_by',
    'updated_by',
    'deleted_at',
]);

it('does not store a slug or a public file path on a document', function (string $column) {
    expect(Schema::hasColumn('documents', $column))->toBeFalse();
})->with(['slug', 'url']);

it('publishes only published documents', function () {
    $published = Document::factory()->published()->create();
    Document::factory()->create();
    Document::factory()->archived()->create();

    expect(Document::query()->published()->pluck('id')->all())
        ->toBe([$published->getKey()]);
});

it('orders published documents deterministically', function () {
    $old = Document::factory()->published()->create(['published_at' => now()->subDays(2)]);
    $new = Document::factory()->published()->create(['published_at' => now()->subDay()]);

    expect(Document::query()->published()->ordered()->pluck('id')->all())
        ->toBe([$new->getKey(), $old->getKey()]);
});

it('marks a document expired only after its date has passed', function () {
    $expired = Document::factory()->expired()->create();
    $future = Document::factory()->create(['expires_at' => now()->addDay()->toDateString()]);
    $none = Document::factory()->create(['expires_at' => null]);

    expect($expired->isExpired())->toBeTrue()
        ->and($future->isExpired())->toBeFalse()
        ->and($none->isExpired())->toBeFalse();
});

it('soft deletes a document without deleting its category', function () {
    $category = DocumentCategory::factory()->create();
    $document = Document::factory()->create(['document_category_id' => $category->getKey()]);

    $document->delete();

    expect(Document::query()->count())->toBe(0)
        ->and(Document::withTrashed()->count())->toBe(1)
        ->and(DocumentCategory::query()->whereKey($category->getKey())->exists())->toBeTrue();
});

it('keeps status as a typed enum', function () {
    $document = Document::factory()->create(['status' => DocumentStatus::Published]);

    expect($document->fresh()->status)->toBe(DocumentStatus::Published);
});
