<?php

namespace App\Concerns;

/**
 * Builds the labels, hints and interface copy shared by admin forms.
 *
 * The four common keys come from one language file per module; module-specific
 * choices are passed as $extra. A module without copy/empty keys passes
 * $withCopy = false so its props stay exactly as before.
 */
trait BuildsFormMeta
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function buildFormMeta(
        string $langKey,
        array $extra = [],
        bool $withCopy = true,
    ): array {
        $meta = [
            'labels' => (array) __($langKey.'.labels'),
            'hints' => (array) __($langKey.'.hints'),
        ];

        if ($withCopy) {
            $meta['copy'] = (array) __($langKey.'.admin');
            $meta['empty'] = (array) __($langKey.'.empty');
        }

        return [...$meta, ...$extra];
    }
}
