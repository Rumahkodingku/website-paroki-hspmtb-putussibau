<?php

namespace App\Concerns;

use App\Services\HtmlSanitizer;

/**
 * Sanitizes the named HTML fields in place, turning an emptied editor into null.
 *
 * Shared by the Profil, Pelayanan and Komunitas form requests so the three
 * modules cannot disagree about what "blank" means.
 */
trait SanitizesHtmlFields
{
    /**
     * @param  list<string>  $fields
     */
    protected function sanitizeHtmlFields(array $fields): void
    {
        $sanitizer = app(HtmlSanitizer::class);
        $merge = [];

        foreach ($fields as $field) {
            $merge[$field] = $sanitizer->cleanOrNull(
                is_string($this->input($field)) ? $this->input($field) : null,
            );
        }

        $this->merge($merge);
    }
}
