import { execFileSync } from 'node:child_process';
import { e2eEnvironment } from './environment';

/*
 * Contact fixtures for the E2E specs.
 *
 * Site settings live in the database, not in a factory the spec can call from
 * the browser, so the values are planted with `php artisan tinker` against the
 * prepared E2E database (the same mechanism tests/e2e/support/gallery.ts uses).
 * The values are obviously fake: no real parish phone, address or email.
 */

function run(script: string): void {
    const environment = e2eEnvironment();

    execFileSync('php', ['artisan', 'tinker', '--execute', script], {
        env: { ...process.env, DB_DATABASE: environment.database },
        stdio: 'ignore',
    });
}

const SEED = String.raw`
app(\App\Services\SiteSettingsService::class)->setMany([
    'contact_address' => "Jalan E2E No. 1\nPutussibau",
    'contact_phone' => '0812-3456-7890',
    'contact_whatsapp' => '0812-3456-7890',
    'contact_email' => 'kontak@example.test',
    'office_hours' => [
        'monday' => ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'],
        'tuesday' => ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'],
        'wednesday' => ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'],
        'thursday' => ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'],
        'friday' => ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'],
        'saturday' => ['is_open' => false, 'open_time' => null, 'close_time' => null],
        'sunday' => ['is_open' => false, 'open_time' => null, 'close_time' => null],
    ],
    'maps_link' => 'https://www.google.com/maps/place/Paroki+E2E/@-0.0293,111.7686,17z',
    'social_facebook' => 'https://facebook.com/contoh',
    'home_show_contact' => true,
]);
`;

const CLEAR = String.raw`
app(\App\Services\SiteSettingsService::class)->setMany([
    'contact_address' => null,
    'contact_phone' => null,
    'contact_whatsapp' => null,
    'contact_email' => null,
    'office_hours' => null,
    'maps_link' => null,
    'social_facebook' => null,
    'social_instagram' => null,
    'social_youtube' => null,
    'social_whatsapp_channel' => null,
    'home_show_contact' => true,
]);
`;

/** Plant a complete, valid contact fixture. */
export function seedContactDetails(): void {
    run(SEED);
}

/** Remove every contact value and re-enable the homepage block. */
export function clearContactDetails(): void {
    run(CLEAR);
}

/** Toggle only `home_show_contact`. */
export function setHomeShowContact(enabled: boolean): void {
    run(
        `app(\\App\\Services\\SiteSettingsService::class)->set('home_show_contact', ${
            enabled ? 'true' : 'false'
        });`,
    );
}
