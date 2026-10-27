<?php

use App\Models\AuditEvent;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Schema;

it('has the audit_events table and its required columns', function () {
    expect(Schema::hasTable('audit_events'))->toBeTrue()
        ->and(Schema::hasColumns('audit_events', [
            'actor_id',
            'actor_type',
            'actor_name',
            'actor_email',
            'action',
            'module',
            'subject_type',
            'subject_id',
            'subject_label',
            'description',
            'result',
            'before',
            'after',
            'metadata',
            'request_id',
            'ip_address',
            'user_agent',
            'occurred_at',
            'created_at',
            'idempotency_key',
        ]))->toBeTrue();
});

it('has a label for every configured code', function () {
    foreach ((array) config('audit.modules') as $module) {
        expect(Lang::has("audit.modules.{$module}"))->toBeTrue();
    }

    foreach ((array) config('audit.actions') as $action) {
        expect(Lang::has("audit.actions.{$action}"))->toBeTrue();
    }

    foreach ((array) config('audit.results') as $result) {
        expect(Lang::has("audit.results.{$result}"))->toBeTrue();
    }
});

it('keeps credential-shaped keys out of every field allowlist', function () {
    $forbidden = ['password', 'token', 'secret', 'authorization', 'cookie', 'session'];

    foreach ((array) config('audit.fields') as $module => $fields) {
        foreach ((array) $fields as $field) {
            foreach ($forbidden as $needle) {
                expect(str_contains(strtolower((string) $field), $needle))->toBeFalse();
            }
        }
    }
});

it('refuses instance updates and deletes', function () {
    $event = AuditEvent::factory()->create();

    expect(fn () => $event->update(['description' => 'ubah']))
        ->toThrow(LogicException::class);

    expect(fn () => $event->delete())
        ->toThrow(LogicException::class);
});
