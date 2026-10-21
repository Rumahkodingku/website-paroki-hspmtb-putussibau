import { router } from '@inertiajs/react';
import { Text } from '@/components/text';
import { index as komunitasIndex } from '@/routes/komunitas';
import type { PublicCommunityCategory, PublicCommunityFilters } from '../types';

/**
 * Search and category filter, both living in the URL.
 *
 * A filtered directory is a place somebody can be sent a link to, so the filters
 * are query parameters rather than component state. `replace` rather than
 * `push`, because typing in a search box should not fill the back button.
 */
export function CommunityFilters({
    filters,
    categories,
    resultCount,
}: {
    filters: PublicCommunityFilters;
    categories: PublicCommunityCategory[];
    resultCount: number;
}) {
    const apply = (next: Partial<PublicCommunityFilters>) => {
        router.get(
            komunitasIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.q !== '' || filters.kategori !== '';

    return (
        <div className="flex flex-wrap items-center gap-3">
            <div className="min-w-56 flex-1">
                <label htmlFor="komunitas-search" className="sr-only">
                    Cari komunitas
                </label>
                <input
                    id="komunitas-search"
                    type="search"
                    value={filters.q}
                    placeholder="Cari nama komunitas"
                    onChange={(event: any) => apply({ q: event.target.value })}
                    className="h-11 w-full rounded-pill border border-border bg-background px-4 text-sm focus-visible:border-primary focus-visible:outline-none"
                />
            </div>

            <div>
                <label htmlFor="komunitas-kategori" className="sr-only">
                    Filter kategori
                </label>
                <select
                    id="komunitas-kategori"
                    value={filters.kategori}
                    onChange={(event: any) =>
                        apply({ kategori: event.target.value })
                    }
                    className="h-11 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
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
                    className="h-11 rounded-md px-3 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    Reset filter
                </button>
            ) : null}

            <Text
                as="p"
                variant="body-sm"
                color="muted"
                role="status"
                aria-live="polite"
            >
                {resultCount} komunitas
            </Text>
        </div>
    );
}
