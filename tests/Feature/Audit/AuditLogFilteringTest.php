<?php

use App\Models\AuditEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Testing\AssertableInertia as Assert;

it('defaults to the newest event first', function () {
    $user = superAdmin();

    $older = AuditEvent::factory()->create([
        'occurred_at' => Carbon::parse('2026-10-01 08:00:00', 'UTC'),
    ]);
    $newer = AuditEvent::factory()->create([
        'occurred_at' => Carbon::parse('2026-10-02 08:00:00', 'UTC'),
    ]);

    $this->actingAs($user)
        ->get(route('audit-logs.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.id', $newer->getKey())
            ->where('events.data.1.id', $older->getKey()));
});

it('filters by module, action, result, and actor', function () {
    $user = superAdmin();
    $other = User::factory()->create();

    $match = AuditEvent::factory()->create([
        'module' => 'news',
        'action' => 'updated',
        'result' => 'success',
        'actor_id' => $other->getKey(),
        'actor_name' => $other->name,
    ]);

    AuditEvent::factory()->create([
        'module' => 'agenda',
        'action' => 'updated',
        'result' => 'success',
    ]);

    AuditEvent::factory()->create([
        'module' => 'news',
        'action' => 'deleted',
        'result' => 'success',
    ]);

    $this->actingAs($user)
        ->get(route('audit-logs.index', [
            'module' => 'news',
            'action' => 'updated',
            'result' => 'success',
            'actor' => 'user:'.$other->getKey(),
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.id', $match->getKey()));
});

it('filters by a date range in the display timezone', function () {
    $user = superAdmin();

    $inside = AuditEvent::factory()->create([
        'occurred_at' => Carbon::parse('2026-10-01 12:00:00', 'UTC'),
    ]);

    AuditEvent::factory()->create([
        'occurred_at' => Carbon::parse('2026-09-30 12:00:00', 'UTC'),
    ]);

    $this->actingAs($user)
        ->get(route('audit-logs.index', [
            'from' => '2026-10-01',
            'to' => '2026-10-01',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('events.data', 1)
            ->where('events.data.0.id', $inside->getKey()));
});

it('ignores unknown filter codes instead of failing', function () {
    $user = superAdmin();
    AuditEvent::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('audit-logs.index', ['module' => 'tidak-ada']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('events.data', 2));
});

it('caps the page size and keeps the query string on pagination links', function () {
    $user = superAdmin();
    AuditEvent::factory()->count(12)->create(['module' => 'news']);

    $this->actingAs($user)
        ->get(route('audit-logs.index', ['module' => 'news', 'per_page' => 1000]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.meta.per_page', 10)
            ->where('events.links.first', fn ($url): bool => is_string($url)
                && str_contains($url, 'module=news')));
});

it('searches only the agreed fields', function () {
    $user = superAdmin();

    AuditEvent::factory()->create([
        'description' => 'Aktivitas terlihat',
        'before' => ['note' => 'rahasia-dalam-snapshot'],
    ]);

    $this->actingAs($user)
        ->get(route('audit-logs.index', ['search' => 'rahasia-dalam-snapshot']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('events.data', 0));
});

it('rejects a date range longer than one year', function () {
    $user = superAdmin();

    $this->actingAs($user)
        ->from(route('audit-logs.index'))
        ->get(route('audit-logs.index', [
            'from' => '2024-01-01',
            'to' => '2026-01-01',
        ]))
        ->assertSessionHasErrors('to');
});

it('sorts by the requested column and direction', function () {
    $user = superAdmin();

    $first = AuditEvent::factory()->create([
        'action' => 'created',
        'occurred_at' => Carbon::parse('2026-10-01 08:00:00', 'UTC'),
    ]);

    $second = AuditEvent::factory()->create([
        'action' => 'updated',
        'occurred_at' => Carbon::parse('2026-10-02 08:00:00', 'UTC'),
    ]);

    $this->actingAs($user)
        ->get(route('audit-logs.index', [
            'sort' => 'action',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.id', $first->getKey())
            ->where('events.data.1.id', $second->getKey())
            ->where('filters.sort', 'action')
            ->where('filters.direction', 'asc'));

    $this->actingAs($user)
        ->get(route('audit-logs.index', [
            'sort' => 'occurred_at',
            'direction' => 'asc',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.id', $first->getKey())
            ->where('events.data.1.id', $second->getKey()));
});

it('falls back to the newest first for an unknown sort key', function () {
    $user = superAdmin();

    $older = AuditEvent::factory()->create([
        'occurred_at' => Carbon::parse('2026-10-01 08:00:00', 'UTC'),
    ]);

    $newer = AuditEvent::factory()->create([
        'occurred_at' => Carbon::parse('2026-10-02 08:00:00', 'UTC'),
    ]);

    $this->actingAs($user)
        ->get(route('audit-logs.index', ['sort' => 'not-a-column']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('events.data.0.id', $newer->getKey())
            ->where('events.data.1.id', $older->getKey()));
});
