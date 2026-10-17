<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use Inertia\Testing\AssertableInertia as Assert;

it('renders the public document directory with published documents only', function () {
    $published = Document::factory()->published()->create();
    Document::factory()->create();
    Document::factory()->archived()->create();

    $this->get('/dokumen')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/dokumen')
            ->has('documents.data', 1)
            ->where('documents.data.0.id', $published->getKey())
            ->missing('documents.data.0.file_path')
            ->missing('documents.data.0.file_disk')
            ->missing('documents.data.0.original_filename'));
});

it('searches title and description', function () {
    Document::factory()->published()->create(['title' => 'Warta Paskah']);
    Document::factory()->published()->create([
        'title' => 'Lainnya',
        'description' => 'Panduan warta paroki',
    ]);

    $this->get('/dokumen?q=Paskah')
        ->assertInertia(fn (Assert $page) => $page->has('documents.data', 1));

    $this->get('/dokumen?q=warta')
        ->assertInertia(fn (Assert $page) => $page->has('documents.data', 2));
});

it('filters by category slug and hides inactive categories from the filter', function () {
    $active = DocumentCategory::factory()->create(['slug' => 'pedoman']);
    $inactive = DocumentCategory::factory()->inactive()->create(['slug' => 'arsip']);

    Document::factory()->published()->create(['document_category_id' => $active->getKey()]);
    Document::factory()->published()->create(['document_category_id' => $inactive->getKey()]);

    $this->get('/dokumen?kategori=pedoman')
        ->assertInertia(fn (Assert $page) => $page->has('documents.data', 1));

    $this->get('/dokumen')
        ->assertInertia(fn (Assert $page) => $page
            ->has('categories', 1)
            ->where('categories.0.slug', 'pedoman'));

    $this->get('/dokumen?kategori=arsip')
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents.data', 2)
            ->where('filters.kategori', ''));
});

it('shows featured documents in a separate prop', function () {
    $featured = Document::factory()->published()->featured()->create();
    Document::factory()->published()->create();

    $this->get('/dokumen')
        ->assertInertia(fn (Assert $page) => $page
            ->has('featured', 1)
            ->where('featured.0.id', $featured->getKey())
            ->has('documents.data', 2));

    $this->get('/dokumen?q=tidak-ada')
        ->assertInertia(fn (Assert $page) => $page->has('featured', 0));
});

it('keeps an expired document visible and downloadable', function () {
    $expired = Document::factory()->published()->expired()->create();

    $this->get('/dokumen')
        ->assertInertia(fn (Assert $page) => $page
            ->where('documents.data.0.id', $expired->getKey())
            ->where('documents.data.0.is_expired', true));
});

it('shows an empty directory without an error', function () {
    $this->get('/dokumen')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('documents.data', 0)
            ->has('featured', 0));
});
