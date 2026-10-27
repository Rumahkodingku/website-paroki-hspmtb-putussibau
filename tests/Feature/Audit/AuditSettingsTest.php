<?php

use App\Models\AuditEvent;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Cache;

it('records only the settings keys that actually changed', function () {
    $user = superAdmin();

    app(SiteSettingsService::class)->set('home_news_limit', 3);

    $this->actingAs($user)
        ->put(route('settings.update'), [
            'settings' => [
                'parish_name' => 'Paroki Baru',
                'home_news_limit' => 3,
            ],
        ])
        ->assertSessionHasNoErrors();

    $event = AuditEvent::query()->sole();

    expect($event->module)->toBe('settings')
        ->and($event->action)->toBe('settings_updated')
        ->and($event->metadata['setting_keys_changed'])->toBe(['parish_name'])
        ->and($event->after)->toBe(['parish_name' => 'Paroki Baru'])
        ->and($event->before)->toBe(['parish_name' => null]);
});

it('summarizes long rich text instead of storing it', function () {
    $user = superAdmin();
    $content = '<p>'.str_repeat('Kebijakan paroki. ', 20).'</p>';

    $this->actingAs($user)
        ->put(route('settings.update'), [
            'settings' => ['privacy_policy_content' => $content],
        ])
        ->assertSessionHasNoErrors();

    $event = AuditEvent::query()->sole();

    expect($event->after['privacy_policy_content'])->toStartWith('[ringkas:')
        ->and($event->metadata['summarized_fields'])->toBe(['privacy_policy_content']);
});

it('drops the settings cache after the save', function () {
    $user = superAdmin();

    app(SiteSettingsService::class)->get('parish_name');

    $this->actingAs($user)
        ->put(route('settings.update'), [
            'settings' => ['parish_name' => 'Nama Baru'],
        ])
        ->assertSessionHasNoErrors();

    expect(Cache::has((string) config('site-settings.cache.key')))->toBeFalse();
});
