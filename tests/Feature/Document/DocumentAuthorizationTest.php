<?php

use App\Models\Document;
use App\Models\DocumentCategory;
use App\Models\User;

it('redirects a guest from the admin document routes', function () {
    $document = Document::factory()->create();

    $this->get(route('documents.index'))->assertRedirect(route('login'));
    $this->get(route('documents.create'))->assertRedirect(route('login'));
    $this->get(route('documents.edit', $document))->assertRedirect(route('login'));
    $this->get(route('documents.preview', $document))->assertRedirect(route('login'));
});

it('refuses a signed-in user without a role', function () {
    $document = Document::factory()->create();

    $this->actingAs(User::factory()->create())
        ->get(route('documents.index'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->get(route('documents.preview', $document))
        ->assertForbidden();
});

it('refuses unauthorized writes', function () {
    $document = Document::factory()->create();
    $category = DocumentCategory::factory()->create();
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('documents.store'), [])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('documents.update', $document), [])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('documents.destroy', $document))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('document-categories.store'), [])
        ->assertForbidden();

    $this->actingAs($user)
        ->patch(route('document-categories.update', $category), [])
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('document-categories.destroy', $category))
        ->assertForbidden();
});
