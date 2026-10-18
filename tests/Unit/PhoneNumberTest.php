<?php

use App\Support\PhoneNumber;

/*
|--------------------------------------------------------------------------
| PhoneNumber
|--------------------------------------------------------------------------
|
| PRD KON-01 / AC-KON-5: a national number is normalised to international
| form, and a number that does not reduce to something plausible produces no
| link at all. The Contact page, Pelayanan and Komunitas all rely on this one
| class, so these are unit tests rather than feature assertions.
|
*/

test('a national number is normalised to international digits', function () {
    expect(PhoneNumber::normalize('0812-3456-7890'))->toBe('6281234567890')
        ->and(PhoneNumber::normalize('+62 812 3456 7890'))->toBe('6281234567890')
        ->and(PhoneNumber::normalize('81234567890'))->toBe('6281234567890');
});

test('a valid number produces tel and WhatsApp links', function () {
    expect(PhoneNumber::toTel('0812-3456-7890'))->toBe('tel:+6281234567890')
        ->and(PhoneNumber::toWhatsApp('0812-3456-7890'))->toBe('https://wa.me/6281234567890');
});

test('junk, empty and null numbers are refused', function () {
    foreach ([null, '', '   ', 'bukan nomor', '123', '12345678901234567890'] as $value) {
        expect(PhoneNumber::isValid($value))->toBeFalse()
            ->and(PhoneNumber::toTel($value))->toBeNull()
            ->and(PhoneNumber::toWhatsApp($value))->toBeNull();
    }
});
