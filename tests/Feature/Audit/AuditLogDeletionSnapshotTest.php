<?php

use App\Actions\Document\DeleteDocumentAction;
use App\Actions\Document\SaveDocumentAction;
use App\Models\AuditEvent;
use App\Models\Document;
use App\Models\Event;

it('keeps an agenda snapshot after the source row is deleted', function () {
    $user = superAdmin();
    $event = Event::factory()->create(['title' => 'Kegiatan Audit']);

    $this->actingAs($user)
        ->delete(route('events.destroy', $event))
        ->assertRedirect();

    $audit = AuditEvent::query()->latest('id')->firstOrFail();

    expect($audit->action)->toBe('deleted')
        ->and($audit->subject_label)->toBe('Kegiatan Audit')
        ->and($audit->before)->toHaveKey('title', 'Kegiatan Audit')
        ->and($audit->after)->toBeNull();
});

it('audits a document update and keeps the delete snapshot', function () {
    $user = superAdmin();
    $document = Document::factory()->create(['title' => 'Dokumen Lama']);

    app(SaveDocumentAction::class)->handle(
        $document,
        ['title' => 'Dokumen Baru'],
        null,
        $user,
    );

    $updated = AuditEvent::query()->latest('id')->firstOrFail();

    expect($updated->module)->toBe('documents')
        ->and($updated->action)->toBe('updated')
        ->and($updated->before)->toHaveKey('title', 'Dokumen Lama')
        ->and($updated->after)->toHaveKey('title', 'Dokumen Baru');

    app(DeleteDocumentAction::class)->handle($document->fresh());

    $deleted = AuditEvent::query()->latest('id')->firstOrFail();

    expect($deleted->action)->toBe('deleted')
        ->and($deleted->subject_label)->toBe('Dokumen Baru')
        ->and($deleted->before)->toHaveKey('title', 'Dokumen Baru')
        ->and(Document::withTrashed()->whereKey($document->getKey())->first()?->trashed())->toBeTrue();
});
