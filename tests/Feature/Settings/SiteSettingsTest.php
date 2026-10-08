<?php

use App\Models\SiteSetting;
use App\Models\User;
use App\Services\SiteSettingsService;
use Database\Seeders\SiteSettingsSeeder;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| Site settings
|--------------------------------------------------------------------------
|
| Covers T16, T17 and T18 from the Phase 01 test matrix plus AC-09, which says a
| Super Admin can read and change site settings and that the cache is invalidated
| afterwards.
|
| The service is resolved fresh in each test rather than injected, because the
| cache lives in the array store for the whole test process and a stale entry
| from an earlier test would otherwise make a broken invalidation look fine.
|
| @see docs/DECISIONS.md D-23
|
*/

/*
 * The array cache store lives for the whole test process, so a map cached by one
 * test would otherwise be handed to the next. Clearing it once per test keeps
 * each one independent.
 */
beforeEach(function () {
    Cache::forget(config('site-settings.cache.key'));
});

function settingsService(): SiteSettingsService
{
    return app(SiteSettingsService::class);
}

/*
|--------------------------------------------------------------------------
| T16 — reading
|--------------------------------------------------------------------------
*/

test('a stored setting is returned', function () {
    SiteSetting::factory()->keyed('parish_name', 'HSPMTB')->create();

    expect(settingsService()->get('parish_name'))->toBe('HSPMTB');
});

test('a missing setting falls back to its configured default', function () {
    // Nothing is seeded, so an unset numeric toggle has to still be usable.
    expect(settingsService()->get('home_news_limit'))->toBe(3)
        ->and(settingsService()->get('home_show_gallery'))->toBeTrue();
});

test('a caller default is used when neither a row nor a configured default exists', function () {
    expect(settingsService()->get('parish_name', 'fallback'))->toBe('fallback');
});

test('numeric settings come back as integers and flags as booleans', function () {
    SiteSetting::factory()->keyed('home_news_limit', '5')->create();
    SiteSetting::factory()->keyed('home_show_gallery', '0')->create();

    $service = settingsService();

    expect($service->get('home_news_limit'))->toBeInt()->toBe(5)
        ->and($service->get('home_show_gallery'))->toBeBool()->toBeFalse();
});

test('a group returns only its own keys, already typed', function () {
    SiteSetting::factory()->keyed('parish_name', 'HSPMTB')->create();
    SiteSetting::factory()->keyed('contact_phone', '1234')->create();
    SiteSetting::factory()->keyed('home_events_limit', '4')->create();

    $identitas = settingsService()->getGroup('identitas');

    expect($identitas)->toHaveKey('parish_name', 'HSPMTB')
        ->and($identitas)->not->toHaveKey('contact_phone')
        // An unset key still appears, with its default, so the form can bind to it.
        ->and($identitas['short_description'])->toBeNull();
});

test('all returns only the rows that exist', function () {
    SiteSetting::factory()->keyed('parish_name', 'HSPMTB')->create();

    expect(settingsService()->all())->toBe(['parish_name' => 'HSPMTB']);
});

/*
|--------------------------------------------------------------------------
| T17 — writing
|--------------------------------------------------------------------------
*/

test('saving writes every key and files it under the right group', function () {
    settingsService()->setMany([
        'parish_name' => 'HSPMTB Putussibau',
        'contact_email' => 'secretariat@example.test',
        'home_news_limit' => 5,
        'home_show_gallery' => false,
    ]);

    $rows = SiteSetting::query()->pluck('group', 'key');

    expect($rows['parish_name'])->toBe('identitas')
        ->and($rows['contact_email'])->toBe('kontak')
        ->and($rows['home_news_limit'])->toBe('beranda');

    $service = settingsService();

    expect($service->get('parish_name'))->toBe('HSPMTB Putussibau')
        ->and($service->get('home_news_limit'))->toBe(5)
        ->and($service->get('home_show_gallery'))->toBeFalse();
});

test('booleans are stored as one and zero in the longtext column', function () {
    settingsService()->setMany([
        'home_show_devotion' => true,
        'home_show_contact' => false,
    ]);

    $stored = SiteSetting::query()->pluck('value', 'key');

    expect($stored['home_show_devotion'])->toBe('1')
        ->and($stored['home_show_contact'])->toBe('0');
});

test('saving twice updates the row instead of creating a second one', function () {
    $service = settingsService();

    $service->setMany(['parish_name' => 'Satu']);
    $service->setMany(['parish_name' => 'Dua']);

    expect(SiteSetting::query()->where('key', 'parish_name')->count())->toBe(1)
        ->and(settingsService()->get('parish_name'))->toBe('Dua');
});

test('unknown keys are refused rather than stored', function () {
    settingsService()->setMany([
        'parish_name' => 'HSPMTB',
        'not_a_real_setting' => 'smuggled',
    ]);

    expect(SiteSetting::query()->where('key', 'not_a_real_setting')->exists())->toBeFalse()
        ->and(settingsService()->get('parish_name'))->toBe('HSPMTB');
});

/*
|--------------------------------------------------------------------------
| T18 — cache invalidation
|--------------------------------------------------------------------------
*/

test('the cache is filled on read and dropped on write', function () {
    $key = config('site-settings.cache.key');

    SiteSetting::factory()->keyed('parish_name', 'Awal')->create();

    // First read populates the cache.
    expect(settingsService()->get('parish_name'))->toBe('Awal')
        ->and(Cache::has($key))->toBeTrue();

    // The value now comes from cache, so a direct database edit stays invisible.
    SiteSetting::query()->where('key', 'parish_name')->update(['value' => 'Diubah diam-diam']);
    expect(settingsService()->get('parish_name'))->toBe('Awal');

    // Writing must drop it.
    settingsService()->setMany(['parish_name' => 'Baru']);

    expect(Cache::has($key))->toBeFalse()
        ->and(settingsService()->get('parish_name'))->toBe('Baru');
});

/*
|--------------------------------------------------------------------------
| Authorization and the page itself (AC-09)
|--------------------------------------------------------------------------
*/

test('a guest cannot reach the settings page', function () {
    $this->get(route('settings.edit'))->assertRedirect(route('login'));
});

test('a user without the super admin role is refused', function () {
    seedRolesAndPermissions();

    $this->actingAs(User::factory()->create())
        ->get(route('settings.edit'))
        ->assertForbidden();

    $this->actingAs(User::factory()->create())
        ->put(route('settings.update'), ['settings' => ['parish_name' => 'X']])
        ->assertForbidden();
});

test('the page renders with every configured group', function () {
    SiteSetting::factory()->keyed('parish_name', 'HSPMTB')->create();

    $this->actingAs(superAdmin())
        ->get(route('settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pengaturan')
            ->has('groups')
            ->has('meta')
            ->where('groups.identitas.parish_name', 'HSPMTB')
            // Unset keys still carry their defaults so the form can bind to them.
            ->where('groups.beranda.home_news_limit', 3)
        );
});

test('the update route saves and reports back in Indonesian', function () {
    $this->actingAs(superAdmin())
        ->from(route('settings.edit'))
        ->put(route('settings.update'), [
            'settings' => [
                'parish_name' => 'HSPMTB Putussibau',
                'home_news_limit' => '6',
                'home_show_gallery' => false,
            ],
        ])
        ->assertRedirect(route('settings.edit'))
        ->assertSessionHasNoErrors();

    $service = settingsService();

    expect($service->get('parish_name'))->toBe('HSPMTB Putussibau')
        ->and($service->get('home_news_limit'))->toBe(6)
        ->and($service->get('home_show_gallery'))->toBeFalse();
});

test('a value outside the documented range is rejected in Indonesian', function () {
    $this->actingAs(superAdmin())
        ->from(route('settings.edit'))
        ->put(route('settings.update'), [
            'settings' => ['home_news_limit' => '99'],
        ])
        ->assertSessionHasErrors('settings.home_news_limit');

    expect(settingsService()->get('home_news_limit'))->toBe(3);
});

test('an unknown key is rejected by the request', function () {
    $this->actingAs(superAdmin())
        ->from(route('settings.edit'))
        ->put(route('settings.update'), [
            'settings' => ['not_a_real_setting' => 'x'],
        ])
        // The error lands on the array as a whole, because the rule inspects the
        // keys rather than each field's value.
        ->assertSessionHasErrors('settings');

    expect(SiteSetting::query()->where('key', 'not_a_real_setting')->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| The configuration itself
|--------------------------------------------------------------------------
*/

test('every key belongs to exactly one group', function () {
    // The service resolves a key to a single group, so an overlap would make the
    // row land somewhere arbitrary and getGroup() would show it twice.
    $groups = (array) config('site-settings.groups');

    $flat = array_merge(...array_values($groups));

    expect($flat)->toBe(array_values(array_unique($flat)));

    foreach ((array) config('site-settings.types') as $key => $type) {
        expect($flat)->toContain($key);
    }

    foreach ((array) config('site-settings.defaults') as $key => $default) {
        expect($flat)->toContain($key);
    }

    foreach ((array) config('site-settings.rules') as $key => $rule) {
        expect($flat)->toContain($key);
    }
});

test('the configured key list matches PRD Lampiran B', function () {
    $keys = app(SiteSettingsService::class)->knownKeys();

    // privacy_policy_content joined the list in P11, which is where
    // HtmlSanitizer lives. It was the one key D-23 held back.
    expect($keys)->toHaveCount(30)
        ->and($keys)->toContain(
            'parish_name',
            'maps_link',
            'seo_default_og_image',
            'home_show_contact',
            'privacy_policy_content',
        );
});

/*
|--------------------------------------------------------------------------
| The privacy policy, which is the first field in the application to store HTML
|--------------------------------------------------------------------------
|
| D-23 deferred this field to P11 because storing HTML without a sanitizer
| would violate PRD D-14. These tests are the reason P11 is more than a service
| with no caller: they assert that what lands in the column is the sanitized
| text and not merely that the response looks clean.
|
| @see docs/DECISIONS.md D-25
|
*/

test('the privacy policy field is configured as html', function () {
    expect(config('site-settings.types.privacy_policy_content'))->toBe('html')
        ->and(config('site-settings.groups.privasi'))->toContain('privacy_policy_content');
});

test('saving the privacy policy strips a script element before it reaches the database', function () {
    $admin = superAdmin();

    $this->actingAs($admin)->put(route('settings.update'), [
        'settings' => [
            'privacy_policy_content' => '<p>Kami menghormati privasi Anda.</p><script>alert(1)</script>',
        ],
    ])->assertRedirect(route('settings.edit'));

    // Asserted against the column, not the response. A redirect that looks fine
    // while the payload is still in the database is exactly the failure this
    // test exists to catch.
    $stored = SiteSetting::query()->where('key', 'privacy_policy_content')->value('value');

    expect($stored)->toBe('<p>Kami menghormati privasi Anda.</p>')
        ->and($stored)->not->toContain('script')
        ->and($stored)->not->toContain('alert');
});

test('saving the privacy policy strips event handler attributes', function () {
    $admin = superAdmin();

    $this->actingAs($admin)->put(route('settings.update'), [
        'settings' => [
            'privacy_policy_content' => '<p onclick="alert(1)">Teks</p>',
        ],
    ]);

    expect(SiteSetting::query()->where('key', 'privacy_policy_content')->value('value'))
        ->toBe('<p>Teks</p>');
});

test('a hostile payload cannot be stored even by someone with settings.update', function () {
    // The editor lives in the browser; the request body is whatever the client
    // sent. Authorization is not a substitute for sanitization, and neither is
    // the fact that only a Super Admin can reach this form.
    $admin = superAdmin();

    $this->actingAs($admin)->put(route('settings.update'), [
        'settings' => [
            'privacy_policy_content' => '<img src="x" onerror="alert(1)"><a href="javascript:alert(1)">j</a>',
        ],
    ]);

    $stored = SiteSetting::query()->where('key', 'privacy_policy_content')->value('value');

    expect($stored)->not->toContain('onerror')
        ->and($stored)->not->toContain('javascript:');
});

test('safe markup in the privacy policy is stored intact', function () {
    $admin = superAdmin();

    $html = '<h2>Purpose</h2><p>Kami <strong>menghormati</strong> data Anda.</p>';

    $this->actingAs($admin)->put(route('settings.update'), [
        'settings' => ['privacy_policy_content' => $html],
    ]);

    expect(SiteSetting::query()->where('key', 'privacy_policy_content')->value('value'))
        ->toBe($html);
});

test('clearing the privacy policy stores null rather than leaving the row alone', function () {
    $admin = superAdmin();

    $this->actingAs($admin)->put(route('settings.update'), [
        'settings' => ['privacy_policy_content' => '<p>Isi lama</p>'],
    ]);

    $this->actingAs($admin)->put(route('settings.update'), [
        'settings' => ['privacy_policy_content' => ''],
    ]);

    // null and not an empty string because Laravel's ConvertEmptyStringsToNull
    // runs before the request is ever built, so the field arrives here as null
    // and normaliseValue() leaves it alone. That is the behaviour the service
    // documents: a setting that was never set is distinguishable from one that
    // was deliberately emptied.
    expect(SiteSetting::query()->where('key', 'privacy_policy_content')->value('value'))
        ->toBeNull()
        ->and(settingsService()->get('privacy_policy_content'))->toBeNull();
});

test('a policy past the length limit is rejected in Indonesian', function () {
    $admin = superAdmin();

    $this->actingAs($admin)->put(route('settings.update'), [
        'settings' => ['privacy_policy_content' => str_repeat('a', 50001)],
    ])->assertSessionHasErrors('settings.privacy_policy_content');

    expect(SiteSetting::query()->where('key', 'privacy_policy_content')->exists())->toBeFalse();
});

test('the length limit measures the sanitized text, not the submitted payload', function () {
    // A payload that is only long because of markup it is not allowed to keep
    // has to fit once the markup is gone. Measuring the input instead would
    // reject it, which reads to the admin as a bug rather than as a limit.
    $admin = superAdmin();

    $this->actingAs($admin)->put(route('settings.update'), [
        'settings' => [
            'privacy_policy_content' => str_repeat('<script>alert(1)</script>', 5000).'<p>Akhir</p>',
        ],
    ])->assertSessionHasNoErrors();

    expect(SiteSetting::query()->where('key', 'privacy_policy_content')->value('value'))
        ->toBe('<p>Akhir</p>');
});

test('the settings page renders the privacy group with the field marked as rich text', function () {
    $admin = superAdmin();

    $this->actingAs($admin)->get(route('settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/pengaturan')
            ->has('groups.privasi')
            ->where('meta.richtext', ['privacy_policy_content'])
            ->where('meta.groupLabels.privasi', 'Privasi')
        );
});

test('a guest cannot write a privacy policy', function () {
    $this->put(route('settings.update'), [
        'settings' => ['privacy_policy_content' => '<p>nakal</p>'],
    ])->assertRedirect(route('login'));

    expect(SiteSetting::query()->where('key', 'privacy_policy_content')->exists())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| The seeder
|--------------------------------------------------------------------------
*/

test('the seeder writes only the two values PRD Lampiran D names', function () {
    $this->seed(SiteSettingsSeeder::class);

    expect(SiteSetting::query()->pluck('value', 'key')->all())->toBe([
        'parish_name' => 'Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau',
        'parish_short_name' => 'HSPMTB',
    ]);
});

test('the seeder files both values under identitas', function () {
    $this->seed(SiteSettingsSeeder::class);

    expect(SiteSetting::query()->pluck('group', 'key')->all())
        ->toBe(['parish_name' => 'identitas', 'parish_short_name' => 'identitas']);
});

test('re-running the seeder does not undo an admin edit', function () {
    $this->seed(SiteSettingsSeeder::class);

    settingsService()->setMany(['parish_short_name' => 'HSPTB-U']);

    $this->seed(SiteSettingsSeeder::class);

    expect(settingsService()->get('parish_short_name'))->toBe('HSPTB-U');
});

test('only those two keys get a row, and unset keys stay unset', function () {
    $this->seed(SiteSettingsSeeder::class);

    $service = settingsService();

    expect(SiteSetting::query()->count())->toBe(2);

    // A key with a configured default is usable even with no row behind it.
    expect($service->get('home_news_limit'))->toBe(3)
        ->and($service->get('home_show_devotion'))->toBeTrue();

    // A text key with no default is genuinely unconfigured, and says so with
    // null rather than pretending to be blank.
    expect($service->get('contact_phone'))->toBeNull()
        ->and($service->get('maps_link'))->toBeNull();
});
