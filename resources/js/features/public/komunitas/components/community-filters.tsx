import { router } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { Check, House, LayoutGrid, Search, Users, X } from 'lucide-react';
import { Text } from '@/components/text';
import { index as komunitasIndex } from '@/routes/komunitas';
import type { PublicCommunityCategory, PublicCommunityFilters } from '../types';
import { SEARCH_DEBOUNCE_MS } from '@/lib/search-debouce';

export function CommunityFilters({
    filters,
    categories,
    resultCount,
}: {
    filters: PublicCommunityFilters;
    categories: PublicCommunityCategory[];
    resultCount: number;
}) {
    const [search, setSearch] = useState(filters.q);
    const [activeCategory, setActiveCategory] = useState(filters.kategori);

    useEffect(() => {
        setSearch(filters.q);
    }, [filters.q]);

    useEffect(() => {
        setActiveCategory(filters.kategori);
    }, [filters.kategori]);

    useEffect(() => {
        const normalizedSearch = search.trim();

        if (normalizedSearch === filters.q.trim()) {
            return;
        }

        const timeout = window.setTimeout(() => {
            router.get(
                komunitasIndex().url,
                {
                    ...filters,
                    q: normalizedSearch,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, SEARCH_DEBOUNCE_MS);

        return () => window.clearTimeout(timeout);
    }, [search, filters]);

    const hasFilter = search.trim() !== '' || activeCategory !== '';

    const applyCategory = (kategori: string) => {
        setActiveCategory(kategori);

        router.get(
            komunitasIndex().url,
            {
                ...filters,
                q: search.trim(),
                kategori,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const resetFilters = () => {
        setSearch('');
        setActiveCategory('');

        router.get(
            komunitasIndex().url,
            {
                ...filters,
                q: '',
                kategori: '',
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <section
            aria-label="Pencarian dan filter komunitas"
            className="space-y-5"
        >
            {/* Search */}
            <div className="relative">
                <label htmlFor="komunitas-search" className="sr-only">
                    Cari komunitas
                </label>

                <Search
                    aria-hidden="true"
                    className="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-muted-foreground"
                />

                <input
                    id="komunitas-search"
                    type="search"
                    value={search}
                    onChange={(event: any) => setSearch(event.target.value)}
                    placeholder="Cari nama komunitas..."
                    autoComplete="off"
                    className="h-14 w-full rounded-lg border border-border bg-background pr-12 pl-12 text-sm transition-colors placeholder:text-muted-foreground focus-visible:border-primary-on-surface focus-visible:ring-2 focus-visible:ring-primary-on-surface/15 focus-visible:outline-none"
                />

                {search ? (
                    <button
                        type="button"
                        onClick={() => setSearch('')}
                        aria-label="Hapus pencarian"
                        className="absolute top-1/2 right-3 flex size-8 -translate-y-1/2 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <X className="size-4" aria-hidden="true" />
                    </button>
                ) : null}
            </div>

            {/* Category chips */}
            <div
                className="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1 sm:flex-wrap sm:overflow-visible"
                aria-label="Kategori komunitas"
            >
                <button
                    type="button"
                    aria-pressed={activeCategory === ''}
                    onClick={() => applyCategory('')}
                    className={`inline-flex h-10 shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none ${
                        activeCategory === ''
                            ? 'border-primary-on-surface bg-primary-on-surface text-white'
                            : 'border-border bg-background text-foreground hover:bg-muted'
                    }`}
                >
                    {activeCategory === '' ? (
                        <Check className="size-4" aria-hidden="true" />
                    ) : (
                        <LayoutGrid className="size-4" aria-hidden="true" />
                    )}
                    Semua
                </button>

                {categories.map((category) => {
                    const isActive = activeCategory === category.slug;

                    return (
                        <button
                            key={category.id}
                            type="button"
                            aria-pressed={isActive}
                            onClick={() => applyCategory(category.slug)}
                            className={`inline-flex h-10 shrink-0 items-center gap-2 rounded-full border px-4 text-sm font-medium transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none ${
                                isActive
                                    ? 'border-primary-on-surface bg-primary-on-surface text-white'
                                    : 'border-border bg-background text-muted-foreground hover:bg-muted hover:text-foreground'
                            }`}
                        >
                            {category.name === 'Organisasi/Kategorial' ? (
                                <Users className="size-4" aria-hidden="true" />
                            ) : category.name === 'Wilayah dan Lingkungan' ? (
                                <House className="size-4" aria-hidden="true" />
                            ) : (
                                <Users className="size-4" aria-hidden="true" />
                            )}

                            {category.name}

                            {isActive ? (
                                <Check
                                    className="size-3.5"
                                    aria-hidden="true"
                                />
                            ) : null}
                        </button>
                    );
                })}
            </div>

            {/* Results summary */}
            <div className="flex flex-wrap items-center justify-between gap-3 border-b border-border/70 pb-4">
                <div className="flex items-baseline gap-2">
                    <Text as="p" variant="h3">
                        {resultCount} komunitas
                    </Text>

                    {hasFilter ? (
                        <span className="text-sm text-muted-foreground">
                            hasil pencarian
                        </span>
                    ) : null}
                </div>

                {hasFilter ? (
                    <button
                        type="button"
                        onClick={resetFilters}
                        className="inline-flex items-center gap-1.5 rounded-md px-2 py-1.5 text-sm text-muted-foreground transition-colors hover:text-primary-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <X className="size-4" aria-hidden="true" />
                        Reset filter
                    </button>
                ) : null}
            </div>
        </section>
    );
}
