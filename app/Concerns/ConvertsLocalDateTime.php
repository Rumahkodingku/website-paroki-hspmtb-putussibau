<?php

namespace App\Concerns;

use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

/**
 * Reads a local wall clock as UTC, without turning bad input into a 500.
 *
 * The conversion cannot be a validation rule (a rule rejects, it never
 * rewrites), so it runs before the rules. A value Carbon cannot parse is
 * returned unchanged, which lets the `date` rule report it as a normal 422.
 */
trait ConvertsLocalDateTime
{
    protected function toUtc(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return $value === '' ? null : $value;
        }

        try {
            return Carbon::parse($value, config('app.display_timezone'))->utc();
        } catch (InvalidFormatException) {
            return $value;
        }
    }
}
