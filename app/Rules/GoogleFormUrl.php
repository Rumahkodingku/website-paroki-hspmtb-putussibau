<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Accepts only a real Google Form URL, or nothing.
 *
 * SVC-05 is explicit that the host has to be read from a parsed URL rather than
 * matched as a substring, because the substring forms are exactly the bypasses
 * that look fine: `docs.google.com.evil.com` contains `docs.google.com`, and
 * `evil.com/?next=docs.google.com/forms/...` contains it too. So the value is put
 * through parse_url() and the scheme, host and path are checked separately.
 *
 *   - scheme must be https (a form is an external destination, not a downgrade),
 *   - host must be docs.google.com with a path under /forms/, or host must be
 *     forms.gle with a short code in the path,
 *   - a URL carrying credentials (user:pass@) is refused outright.
 *
 * The rule is nullable at the call site: a service without a Google Form is a
 * valid service, so the request applies this only when a value is present.
 *
 * @see docs/PRD.md SVC-05, XC-S3
 * @see docs/DECISIONS.md D-54
 */
class GoogleFormUrl implements ValidationRule
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

        $parts = parse_url(trim($value));

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            $fail($this->message());

            return;
        }

        if (strtolower($parts['scheme']) !== 'https') {
            $fail($this->message());

            return;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            $fail($this->message());

            return;
        }

        $host = strtolower($parts['host']);
        $path = $parts['path'] ?? '';

        $isDocsForm = $host === 'docs.google.com'
            && str_starts_with(strtolower($path), '/forms/');

        $isShortLink = $host === 'forms.gle'
            && trim($path, '/') !== '';

        if (! $isDocsForm && ! $isShortLink) {
            $fail($this->message());
        }
    }

    private function message(): string
    {
        return 'Tautan Google Form harus berupa alamat https ke docs.google.com/forms/... atau forms.gle/....';
    }
}
