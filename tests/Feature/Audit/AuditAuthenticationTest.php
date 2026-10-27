<?php

use App\Models\AuditEvent;
use App\Models\User;

it('records a successful login without credentials', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->action)->toBe('login_succeeded')
        ->and($event->actor_id)->toBe($user->getKey())
        ->and($event->actor_email)->toBe($user->email)
        ->and($event->before)->toBeNull()
        ->and($event->after)->toBeNull();
});

it('records a failed login with the attempted email and no password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->action)->toBe('login_failed')
        ->and($event->result)->toBe('failure')
        ->and($event->actor_type)->toBe('anonymous')
        ->and($event->actor_email)->toBe($user->email)
        ->and(json_encode([$event->before, $event->after, $event->metadata]))
        ->not->toContain('wrong-password');
});

it('records logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'));

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->action)->toBe('logout')
        ->and($event->actor_id)->toBe($user->getKey());
});

it('records a profile update and an email change', function () {
    $user = superAdmin();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => $user->email,
        ])
        ->assertSessionHasNoErrors();

    $profile = AuditEvent::query()->latest('id')->firstOrFail();

    expect($profile->action)->toBe('profile_updated')
        ->and($profile->after)->toHaveKey('name', 'Nama Baru');

    $this->actingAs($user->fresh())
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'email-baru-'.uniqid().'@example.test',
        ])
        ->assertSessionHasNoErrors();

    $email = AuditEvent::query()->latest('id')->firstOrFail();

    expect($email->action)->toBe('email_changed')
        ->and($email->after)->toHaveKey('email');
});

it('records a password change without the values', function () {
    $user = superAdmin();

    $this->actingAs($user)
        ->put(route('user-password.update'), [
            'current_password' => 'password',
            'password' => 'password-baru',
            'password_confirmation' => 'password-baru',
        ])
        ->assertSessionHasNoErrors();

    $event = AuditEvent::query()->latest('id')->firstOrFail();

    expect($event->action)->toBe('password_changed')
        ->and($event->before)->toBeNull()
        ->and($event->after)->toBeNull()
        ->and(json_encode([$event->before, $event->after, $event->metadata]))
        ->not->toContain('password-baru');
});
