<?php

use App\Models\AuditEvent;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

it('redirects a guest to the login page', function () {
    $this->get(route('audit-logs.index'))->assertRedirect(route('login'));
});

it('forbids a user without auditlogs.view', function () {
    seedRolesAndPermissions();

    $user = User::factory()->create();
    $user->givePermissionTo('admin.access');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($user->fresh())
        ->get(route('audit-logs.index'))
        ->assertForbidden();
});

it('lets a super admin open the list and the detail page', function () {
    $user = superAdmin();
    $event = AuditEvent::factory()->create([
        'actor_id' => $user->getKey(),
        'actor_name' => $user->name,
        'module' => 'news',
        'action' => 'published',
        'result' => 'success',
        'subject_label' => 'Judul Audit',
        'before' => ['status' => 'draft'],
        'after' => ['status' => 'published'],
    ]);

    $this->actingAs($user)
        ->get(route('audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/audit-log')
            ->where('abilities', fn ($abilities): bool => $abilities['auditlogs.view'] === true)
            ->has('events.data', 1));

    $this->actingAs($user)
        ->get(route('audit-logs.show', $event))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/audit-log/show')
            ->where('event.id', $event->getKey())
            ->where('event.actor.label', $user->name)
            ->where('event.action_label', 'Diterbitkan')
            ->where('event.module_label', 'Berita')
            ->where('event.result_label', 'Berhasil')
            ->where('event.subject_label', 'Judul Audit')
            ->where('event.before.status', 'draft')
            ->where('event.after.status', 'published'));
});

it('returns 404 for an unknown event', function () {
    $this->actingAs(superAdmin())
        ->get(route('audit-logs.show', 999999))
        ->assertNotFound();
});

it('does not expose mutation routes for audit events', function () {
    $user = superAdmin();
    $event = AuditEvent::factory()->create();

    $this->actingAs($user)->post(route('audit-logs.index'))->assertStatus(405);
    $this->actingAs($user)->delete(route('audit-logs.show', $event))->assertStatus(405);
});
