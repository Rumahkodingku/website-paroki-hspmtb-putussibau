<?php

namespace App\Concerns;

use App\Enums\MediaStatus;
use App\Services\HtmlSanitizer;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Shared validation helpers for the Profil Paroki form requests.
 *
 * Two things recur across all of them and are easy to get subtly wrong, so they
 * live here rather than being copied into twelve classes:
 *
 *   1. A media reference must point at a row whose status is Ready. An upload
 *      goes through the queue, so a row exists for a moment with no variants on
 *      disk; storing its id then would publish an image that 404s. Same rule the
 *      Berita and Agenda requests use.
 *
 *   2. Rich text is sanitized on the way in, before the rules run, because the
 *      editor being in the browser says nothing about the request body (PRD
 *      D-14, XC-S1). An emptied field becomes null rather than an empty string.
 *
 * @see docs/DECISIONS.md D-25, D-52
 */
trait ProfilValidationRules
{
    /**
     * A `Rule::exists` for a media row that finished processing.
     */
    protected function readyMediaRule(): Exists
    {
        return Rule::exists('media', 'id')->where('status', MediaStatus::Ready->value);
    }

    /**
     * Sanitize each named HTML field in place.
     *
     * @param  list<string>  $fields
     */
    protected function sanitizeHtmlFields(array $fields): void
    {
        $sanitizer = app(HtmlSanitizer::class);
        $merge = [];

        foreach ($fields as $field) {
            $clean = $sanitizer->clean(
                is_string($this->input($field)) ? $this->input($field) : null,
            );

            $merge[$field] = $clean === '' ? null : $clean;
        }

        $this->merge($merge);
    }

    /**
     * The media-ready message, shared so the wording does not drift.
     */
    protected function mediaReadyMessage(): string
    {
        return 'Gambar yang dipilih belum selesai diproses. Tunggu sebentar lalu coba lagi.';
    }
}
