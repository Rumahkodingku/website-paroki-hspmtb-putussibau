<?php

use App\Actions\Audit\RecordAuditEventAction;
use App\Http\Resources\Audit\AuditEventDetailResource;
use App\Models\AuditEvent;
use App\Services\AuditLogger;

it('drops sensitive keys before writing the event', function () {
    app(RecordAuditEventAction::class)->handle([
        'actor_type' => 'user',
        'module' => 'users',
        'action' => 'updated',
        'result' => 'success',
        'description' => 'Profil diperbarui.',
        'before' => ['name' => 'Lama', 'password' => 'secret', 'token' => 'abc'],
        'after' => ['name' => 'Baru', 'password_confirmation' => 'secret'],
        'metadata' => ['secret' => 'hidden', 'reason_code' => 'ok'],
    ]);

    $event = AuditEvent::query()->sole();

    expect($event->before)->toBe(['name' => 'Lama'])
        ->and($event->after)->toBe(['name' => 'Baru'])
        ->and($event->metadata)->toBeNull()
        ->and(json_encode($event->before))->not->toContain('secret')
        ->and(json_encode($event->metadata))->not->toContain('hidden');
});

it('summarizes rich text settings instead of storing the full HTML', function () {
    $this->actingAs(superAdmin());

    $html = '<p>'.str_repeat('Kebijakan privasi paroki. ', 30).'</p>';

    app(AuditLogger::class)->settingsUpdated(
        ['privacy_policy_content' => null, 'parish_name' => 'Lama'],
        ['privacy_policy_content' => $html, 'parish_name' => 'Baru'],
        ['privacy_policy_content', 'parish_name'],
    );

    $event = AuditEvent::query()->sole();

    expect($event->after['privacy_policy_content'])->toStartWith('[ringkas:')
        ->and($event->after['parish_name'])->toBe('Baru')
        ->and($event->metadata['summarized_fields'])->toContain('privacy_policy_content')
        ->and(json_encode($event->after))->not->toContain('Kebijakan privasi paroki.');
});

it('records a password change without the password value', function () {
    $user = superAdmin();
    $this->actingAs($user);

    app(AuditLogger::class)->passwordChanged($user);

    $event = AuditEvent::query()->sole();

    expect($event->action)->toBe('password_changed')
        ->and($event->before)->toBeNull()
        ->and($event->after)->toBeNull()
        ->and($event->metadata)->toBeNull()
        ->and(json_encode([$event->before, $event->after, $event->metadata]))
        ->not->toContain('password');
});

it('leaves HTML as text in the detail resource', function () {
    $event = AuditEvent::factory()->create([
        'before' => ['title' => '<script>alert(1)</script>'],
        'after' => ['title' => '<b>aman</b>'],
    ]);

    $detail = AuditEventDetailResource::make($event)->resolve();

    expect($detail['before']['title'])->toBe('<script>alert(1)</script>')
        ->and($detail['after']['title'])->toBe('<b>aman</b>');
});
