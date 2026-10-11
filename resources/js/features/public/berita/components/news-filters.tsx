import { router } from '@inertiajs/react';
import { Text } from '@/components/text';
import { index as beritaIndex } from '@/routes/berita';
import type { PublicCategory, PublicPostFilters } from '../types';

/**
 * Search and category filter, both living in the URL.
 *
 * A filtered list is a place somebody can be sent a link to, so both are query
 * parameters rather than component state — the same reason the Jadwal Misa
 * filter works this way. `replace` rather than `push`, because typing in a
 * search box should not fill the back button with one entry per keystroke.
 */
export function NewsFilters({
    filters,
    categories,
    resultCount,
}: {
    filters: PublicPostFilters;
    categories: PublicCategory[];
    resultCount: number;
}) {
    const apply = (next: Partial<PublicPostFilters>) => {
        router.get(
            beritaIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.q !== '' || filters.kategori !== '';

    return (
        <div className="flex flex-wrap items-center gap-3">
            <div className="min-w-56 flex-1">
                <label htmlFor="berita-search" className="sr-only">
                    Cari berita
                </label>
                <input
                    id="berita-search"
                    type="search"
                    value={filters.q}
                    placeholder="Cari judul atau isi berita"
                    onChange={(event) => apply({ q: event.target.value })}
                    className="h-11 w-full rounded-pill border border-hairline bg-background px-4 text-sm focus-visible:border-primary focus-visible:outline-none"
                />
            </div>

            <div>
                <label htmlFor="berita-kategori" className="sr-only">
                    Filter kategori
                </label>
                <select
                    id="berita-kategori"
                    value={filters.kategori}
                    onChange={(event) =>
                        apply({ kategori: event.target.value })
                    }
                    className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                >
                    <option value="">Semua kategori</option>
                    {categories.map((category) => (
                        <option key={category.id} value={category.slug}>
                            {category.name}
                        </option>
                    ))}
                </select>
            </div>

            {hasFilter ? (
                <button
                    type="button"
                    onClick={() => apply({ q: '', kategori: '' })}
                    className="h-11 rounded-md px-3 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                >
                    Reset filter
                </button>
            ) : null}

            {/*
             * A live region, so a screen-reader user hears the result change
             * after typing rather than only seeing it. Polite rather than
             * assertive: this is a status, not an alert.
             */}
            <Text
                as="p"
                variant="caption"
                color="muted"
                role="status"
                aria-live="polite"
            >
                {resultCount} berita
            </Text>
        </div>
    );
}
