<?php

namespace App\Rules;

use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Accepts a phone number the parish can dial, or nothing.
 *
 * The parser lives in App\Support\PhoneNumber; this only turns its answer into
 * a validation failure so the area, community and service forms cannot drift.
 */
class PhoneNumberRule implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || trim($value) === '') {
            return;
        }

        if (! PhoneNumber::isValid($value)) {
            $fail('Nomor telepon tidak dikenali. Gunakan angka, boleh dengan awalan 0 atau +62.');
        }
    }
}
