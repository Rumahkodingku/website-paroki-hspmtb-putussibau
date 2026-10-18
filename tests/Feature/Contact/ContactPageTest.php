<?php

use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Cache;
use Inertia\Testing\AssertableInertia as Assert;

/*
|--------------------------------------------------------------------------
| The public contact page
|--------------------------------------------------------------------------
|
| KON-01 makes site settings the single source and KON-03..KON-09 make every
| action conditional on the value being valid. The `contact` shared prop is the
| single contract the page, footer and homepage all read, so these assertions
| describe that contract rather than any one component.
|
| @see docs/DECISIONS.md D-67
|
*/

beforeEach(function () {
    Cache::forget(config('site-settings.cache.key'));
});

/**
 * @param  array<string, mixed>  $values
 */
function contactSettings(array $values): void
{
    app(SiteSettingsService::class)->setMany($values);
}

test('a guest can open the contact page', function () {
    $this->get(route('kontak.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('public/kontak')
            ->has('contact')
        );
});

test('an unconfigured parish gets nulls, not empty strings or fake links', function () {
    $this->get(route('kontak.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.address', null)
            ->where('contact.phone', null)
            ->where('contact.whatsapp', null)
            ->where('contact.email', null)
            ->where('contact.officeHours', null)
            ->where('contact.mapsEmbedUrl', null)
            ->where('contact.mapsLink', null)
            ->where('contact.social.facebook', null)
            // The configured default is visible; it only gates the homepage block.
            ->where('contact.homeShowContact', true)
        );
});

test('configured contact details produce the expected action links', function () {
    $officeHours = [];
    foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday'] as $day) {
        $officeHours[$day] = ['is_open' => true, 'open_time' => '08:00', 'close_time' => '15:00'];
    }
    foreach (['saturday', 'sunday'] as $day) {
        $officeHours[$day] = ['is_open' => false, 'open_time' => null, 'close_time' => null];
    }

    contactSettings([
        'contact_address' => "Jalan Contoh No. 1\nPutussibau",
        'contact_phone' => '0812-3456-7890',
        'contact_whatsapp' => '+62 812 3456 7890',
        'contact_email' => 'sekretariat@example.test',
        'office_hours' => $officeHours,
        'maps_link' => 'https://www.google.com/maps/place/Paroki+HSPMTB/@-0.0293,111.7686,17z',
        'social_facebook' => 'https://facebook.com/paroki',
        'social_instagram' => 'https://instagram.com/paroki',
        'social_youtube' => 'https://youtube.com/@paroki',
        'social_whatsapp_channel' => 'https://whatsapp.com/channel/paroki',
    ]);

    $this->get(route('kontak.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.address', "Jalan Contoh No. 1\nPutussibau")
            ->where('contact.phone.display', '0812-3456-7890')
            ->where('contact.phone.tel', 'tel:+6281234567890')
            ->where('contact.whatsapp.url', 'https://wa.me/6281234567890')
            ->where('contact.email', 'sekretariat@example.test')
            // The weekly schedule is formatted into display lines on the server.
            ->where('contact.officeHours', "Senin–Jumat: 08.00–15.00 WIB\nSabtu: Tutup\nMinggu: Tutup")
            // The iframe is derived from the same link, not stored separately.
            ->where('contact.mapsEmbedUrl', 'https://www.google.com/maps?q=Paroki%20HSPMTB&z=17&output=embed')
            ->where('contact.mapsLink', 'https://www.google.com/maps/place/Paroki+HSPMTB/@-0.0293,111.7686,17z')
            ->where('contact.social.facebook', 'https://facebook.com/paroki')
            ->where('contact.social.instagram', 'https://instagram.com/paroki')
            ->where('contact.social.youtube', 'https://youtube.com/@paroki')
            ->where('contact.social.whatsappChannel', 'https://whatsapp.com/channel/paroki')
        );
});

test('a legacy office hours string is still shown as text', function () {
    contactSettings(['office_hours' => 'Senin–Jumat, 08.00–15.00']);

    $this->get(route('kontak.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.officeHours', 'Senin–Jumat, 08.00–15.00')
        );
});

test('an invalid phone number produces no call or WhatsApp action', function () {
    contactSettings(['contact_phone' => 'bukan nomor']);

    $this->get(route('kontak.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.phone', null)
            ->where('contact.whatsapp', null)
        );
});

test('WhatsApp falls back to the phone number when its own setting is empty', function () {
    contactSettings(['contact_phone' => '0812 3456 789']);

    $this->get(route('kontak.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.whatsapp.url', 'https://wa.me/628123456789')
        );
});

test('an invalid email produces no mailto value', function () {
    contactSettings(['contact_email' => 'bukan-email']);

    $this->get(route('kontak.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.email', null)
        );
});

test('an unsafe map link stored outside the form is not sent to the page', function () {
    // setMany() writes straight to the service, bypassing the FormRequest, so
    // this pins the read-side guard rather than the write-side rule.
    contactSettings(['maps_link' => 'javascript:alert(1)']);

    $this->get(route('kontak.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.mapsEmbedUrl', null)
            ->where('contact.mapsLink', null)
        );
});

test('a social value that is not an http URL is not sent as a link', function () {
    contactSettings([
        'social_facebook' => 'javascript:alert(1)',
        'social_instagram' => 'facebook.com/paroki',
    ]);

    $this->get(route('kontak.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.social.facebook', null)
            ->where('contact.social.instagram', null)
        );
});

test('home_show_contact is exposed as a boolean', function () {
    contactSettings(['home_show_contact' => false]);

    $this->get(route('kontak.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('contact.homeShowContact', false)
        );
});
