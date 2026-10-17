<?php

use App\Models\Document;
use App\Models\DocumentCategory;

it('creates a category and derives its slug', function () {
    $this->actingAs(superAdmin())
        ->post(route('document-categories.store'), [
            'name' => 'Formulir Pelayanan',
            'slug' => '',
            'description' => 'Formulir yang dipakai saat mendaftar.',
            'sort_order' => 2,
            'is_active' => true,
        ])
        ->assertRedirect(route('document-categories.index'));

    $category = DocumentCategory::query()->sole();

    expect($category->name)->toBe('Formulir Pelayanan')
        ->and($category->slug)->toBe('formulir-pelayanan')
        ->and($category->sort_order)->toBe(2);
});

it('rejects a duplicate slug', function () {
    DocumentCategory::factory()->create(['slug' => 'pedoman']);

    $this->actingAs(superAdmin())
        ->post(route('document-categories.store'), [
            'name' => 'Pedoman Lain',
            'slug' => 'pedoman',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('slug');
});

it('lists categories with a document count', function () {
    $category = DocumentCategory::factory()->create();
    Document::factory()->count(2)->create(['document_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->get(route('document-categories.index'))
        ->assertInertia(fn ($page) => $page
            ->component('admin/master-data/kategori-dokumen/index')
            ->where('categories.data.0.documents_count', 2));
});

it('updates a category', function () {
    $category = DocumentCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->patch(route('document-categories.update', $category), [
            'name' => 'Nama Baru',
            'slug' => $category->slug,
            'sort_order' => 3,
            'is_active' => false,
        ])
        ->assertRedirect(route('document-categories.index'));

    expect($category->fresh()->name)->toBe('Nama Baru')
        ->and($category->fresh()->is_active)->toBeFalse();
});

it('refuses to delete a category still used by a document', function () {
    $category = DocumentCategory::factory()->create();
    Document::factory()->create(['document_category_id' => $category->getKey()]);

    $this->actingAs(superAdmin())
        ->delete(route('document-categories.destroy', $category))
        ->assertRedirect(route('document-categories.index'))
        ->assertSessionHasErrors('document_category');

    expect(DocumentCategory::query()->whereKey($category->getKey())->exists())->toBeTrue();
});

it('counts soft-deleted documents when refusing a category delete', function () {
    $category = DocumentCategory::factory()->create();
    $document = Document::factory()->create(['document_category_id' => $category->getKey()]);
    $document->delete();

    $this->actingAs(superAdmin())
        ->delete(route('document-categories.destroy', $category))
        ->assertSessionHasErrors('document_category');

    expect(DocumentCategory::query()->whereKey($category->getKey())->exists())->toBeTrue();
});

it('deletes an unused category', function () {
    $category = DocumentCategory::factory()->create();

    $this->actingAs(superAdmin())
        ->delete(route('document-categories.destroy', $category))
        ->assertRedirect(route('document-categories.index'));

    expect(DocumentCategory::query()->whereKey($category->getKey())->exists())->toBeFalse();
});
