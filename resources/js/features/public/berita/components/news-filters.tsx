import { useEffect, useState, type ChangeEvent, type FormEvent } from 'react';
import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { index as beritaIndex } from '@/routes/berita';

import type { PublicCategory, PublicPostFilters } from '../types';

export function NewsFilters({
    filters,
    categories,
    resultCount,
}: {
    filters: PublicPostFilters;
    categories: PublicCategory[];
    resultCount: number;
}) {
    const [searchQuery, setSearchQuery] = useState(filters.q);

    useEffect(() => {
        setSearchQuery(filters.q);
    }, [filters.q]);

    const hasFilter = filters.q !== '' || filters.kategori !== '';

    const apply = (next: Partial<PublicPostFilters>) => {
        router.get(
            beritaIndex().url,
            {
                ...filters,
                q: searchQuery,
                ...next,
            },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    const handleSearch = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        apply({ q: searchQuery });
    };

    const resetFilters = () => {
        setSearchQuery('');

        router.get(
            beritaIndex().url,
            { q: '', kategori: '' },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    };

    return (
        <section aria-label="Pencarian dan filter berita" className="space-y-4">
            {/* Search */}
            <form
                role="search"
                onSubmit={handleSearch}
                className="flex w-full items-stretch gap-2"
            >
                <div className="relative min-w-0 flex-1">
                    <label htmlFor="berita-search" className="sr-only">
                        Cari judul atau isi berita
                    </label>

                    <Search
                        aria-hidden="true"
                        className="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground"
                    />

                    <Input
                        id="berita-search"
                        type="search"
                        value={searchQuery}
                        onChange={(event: ChangeEvent<HTMLInputElement>) =>
                            setSearchQuery(event.target.value)
                        }
                        placeholder="Cari judul atau isi berita..."
                        className="h-11 rounded-lg py-0 pl-11"
                    />
                </div>

                <Button type="submit" size="icon" aria-label="Cari berita">
                    <Search aria-hidden="true" />
                </Button>
            </form>

            {/* Category filters */}
            <div
                role="group"
                aria-label="Kategori berita"
                className="flex flex-wrap items-center gap-2"
            >
                <Button
                    type="button"
                    size="sm"
                    variant={filters.kategori === '' ? 'default' : 'secondary'}
                    aria-pressed={filters.kategori === ''}
                    onClick={() => apply({ kategori: '' })}
                    className="h-11 min-h-11 rounded-full px-4 text-xs"
                >
                    Semua
                </Button>

                {categories.map((category) => {
                    const isActive = filters.kategori === category.slug;

                    return (
                        <Button
                            key={category.id}
                            type="button"
                            size="sm"
                            variant={isActive ? 'default' : 'secondary'}
                            aria-pressed={isActive}
                            onClick={() => apply({ kategori: category.slug })}
                            className="h-11 min-h-11 rounded-full px-4 text-xs"
                        >
                            {category.name}
                        </Button>
                    );
                })}
            </div>

            {/* Result summary */}
            <Separator />

            <div className="flex min-h-8 flex-wrap items-center justify-between gap-3">
                <div
                    role="status"
                    aria-live="polite"
                    className="flex items-center gap-2"
                >
                    <Text as="p" variant="body-sm" color="muted">
                        {hasFilter ? 'Hasil pencarian' : 'Berita terbaru'}
                    </Text>

                    <Badge
                        variant="secondary"
                        className="tabular-nums"
                        aria-label={`${resultCount} berita`}
                    >
                        {resultCount}
                    </Badge>
                </div>

                {hasFilter && (
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        onClick={resetFilters}
                        className="h-11 gap-1.5 text-muted-foreground"
                    >
                        <X aria-hidden="true" className="size-4" />
                        Reset filter
                    </Button>
                )}
            </div>
        </section>
    );
}
