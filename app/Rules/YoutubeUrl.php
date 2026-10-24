<?php

namespace App\Rules;

use App\Support\YoutubeEmbed;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Accepts only a YouTube URL the public profile can embed.
 *
 * @see docs/DECISIONS.md D-78
 */
class YoutubeUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || YoutubeEmbed::id($value) === null) {
            $fail('Tautan video harus berupa tautan YouTube yang valid.');
        }
    }
}
