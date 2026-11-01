<?php

namespace App\Enums;

/**
 * The editorial categories a livestream can belong to.
 *
 * Four stable slugs rather than a master-data module: the archive needs a chip
 * and a filter, and a CRUD screen for four values nothing else reads would be
 * a module around a vocabulary (D-2).
 *
 * @see docs/DECISIONS.md D-101
 */
enum LivestreamCategory: string
{
    /** Sunday Mass and daily Mass. */
    case Liturgi = 'liturgi';

    /** Christmas, Easter, Pentecost and other holy days. */
    case HariRaya = 'hari_raya';

    /** Rosary, adoration, shared prayer. */
    case Devosi = 'devosi';

    /** Parish celebrations and other activities. */
    case Kegiatan = 'kegiatan';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return (string) __('livestreaming.categories.'.$this->value);
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            fn (self $category): array => [
                'value' => $category->value,
                'label' => $category->label(),
            ],
            self::cases(),
        );
    }
}
