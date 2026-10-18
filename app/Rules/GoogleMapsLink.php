<?php

namespace App\Rules;

use App\Support\MapsUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Accepts only a Google Maps link (or an iframe containing one), or nothing.
 *
 * The parsing lives in App\Support\MapsUrl so this rule, the settings write
 * path, and the public read path all agree. This class only decides whether to
 * fail and what to say.
 *
 * A shortened link gets its own message. It is a real Google Maps link, so the
 * generic "use a Google Maps link" wording would be misleading; the problem is
 * that it carries no coordinates, and the fix is to copy the longer link.
 *
 * The rule is nullable at the call site: a parish without a map is a valid
 * parish, so the request applies it only when a value is present.
 *
 * @see docs/PRD.md KON-02, KON-07, XC-S2
 * @see docs/DECISIONS.md D-67
 */
class GoogleMapsLink implements ValidationRule
{
    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail($this->message());

            return;
        }

        if (MapsUrl::isShortLink($value)) {
            $fail($this->shortLinkMessage());

            return;
        }

        if (! MapsUrl::isGoogleMapsLink($value)) {
            $fail($this->message());
        }
    }

    private function message(): string
    {
        return 'Tautan peta harus berupa link Google Maps (https://www.google.com/maps/...) atau kode embed Google Maps.';
    }

    private function shortLinkMessage(): string
    {
        return 'Link Google Maps yang dipendekkan tidak memuat lokasi. Buka Google Maps, tekan Bagikan, lalu salin link panjangnya.';
    }
}
