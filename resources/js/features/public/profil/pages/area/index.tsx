import { useMemo, useState } from 'react';
import {
    ArrowRight,
    Church,
    MapPin,
    Search,
    SearchX,
    UsersRound,
} from 'lucide-react';

import { EmptyState } from '@/components/empty-state';
import { PublicSection } from '@/components/public-section';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as profilIndex } from '@/routes/profil';
import PublicHeroBanner from '@/components/public-hero-banner';
import { AreaCard } from '../../components/area-card';
import type { PublicAreaSection } from '../../types';

type Props = {
    sections: PublicAreaSection[];
    hasAreas: boolean;
};

type AreaFilter = 'semua' | 'stasi' | 'lingkungan';

export default function Index({ sections, hasAreas }: Props) {
    const [search, setSearch] = useState('');
    const [activeFilter, setActiveFilter] = useState<AreaFilter>('semua');

    const query = search.trim().toLocaleLowerCase('id-ID');

    const stats = useMemo(() => {
        return sections.reduce(
            (result, section) => {
                result[section.type] =
                    (result[section.type] ?? 0) + section.areas.length;

                result.total += section.areas.length;

                return result;
            },
            {
                stasi: 0,
                lingkungan: 0,
                wilayah: 0,
                total: 0,
            } as Record<string, number>,
        );
    }, [sections]);

    const filteredSections = useMemo(() => {
        return sections
            .filter(
                (section) =>
                    activeFilter === 'semua' || section.type === activeFilter,
            )
            .map((section) => ({
                ...section,
                areas: section.areas.filter((area) =>
                    area.name.toLocaleLowerCase('id-ID').includes(query),
                ),
            }))
            .filter((section) => section.areas.length > 0);
    }, [sections, activeFilter, query]);

    const resultCount = filteredSections.reduce(
        (sum, section) => sum + section.areas.length,
        0,
    );

    const resetFilters = () => {
        setSearch('');
        setActiveFilter('semua');
    };

    const filters: {
        value: AreaFilter;
        label: string;
    }[] = [
        { value: 'semua', label: 'Semua' },
        { value: 'stasi', label: 'Stasi' },
        { value: 'lingkungan', label: 'Lingkungan' },
    ];

    return (
        <>
            <Seo />
            <PublicHeroBanner
                eyebrow="PROFIL PAROKI"
                title="Area"
                description="Mengenal lebih dekat komunitas umat yang menjadi bagian dari kehidupan Gereja Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Profil', href: profilIndex() },
                    { title: 'Area' },
                ]}
            />

            <PublicSection>
                <div className="space-y-10 pb-8">
                    {/* Search and filters */}
                    {hasAreas && (
                        <section
                            aria-label="Cari dan filter area"
                            className="space-y-4"
                        >
                            <div className="flex flex-col gap-4 lg:flex-row lg:items-center">
                                <div className="relative min-w-0 flex-1">
                                    <Search
                                        className="pointer-events-none absolute top-1/2 left-4 size-4 -translate-y-1/2 text-muted-foreground"
                                        aria-hidden="true"
                                    />

                                    <input
                                        type="search"
                                        value={search}
                                        onChange={(
                                            event: React.ChangeEvent<HTMLInputElement>,
                                        ) => setSearch(event.target.value)}
                                        placeholder="Cari nama stasi atau lingkungan..."
                                        aria-label="Cari nama stasi atau lingkungan"
                                        className="h-12 w-full rounded-xl border border-border bg-background pr-4 pl-11 text-sm transition-colors outline-none placeholder:text-muted-foreground focus-visible:border-primary focus-visible:ring-2 focus-visible:ring-ring/20"
                                    />
                                </div>

                                <div
                                    className="flex w-full gap-2 overflow-x-auto pb-1 lg:w-auto lg:pb-0"
                                    role="group"
                                    aria-label="Filter kategori area"
                                >
                                    {filters.map((filter) => {
                                        const isActive =
                                            activeFilter === filter.value;

                                        return (
                                            <Button
                                                key={filter.value}
                                                type="button"
                                                variant={
                                                    isActive
                                                        ? 'default'
                                                        : 'outline'
                                                }
                                                aria-pressed={isActive}
                                                onClick={() =>
                                                    setActiveFilter(
                                                        filter.value,
                                                    )
                                                }
                                                className="min-h-11 shrink-0 rounded-full px-5 text-sm"
                                            >
                                                {filter.label}
                                            </Button>
                                        );
                                    })}
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    aria-live="polite"
                                >
                                    Menampilkan{' '}
                                    <span className="font-semibold text-foreground">
                                        {resultCount}
                                    </span>{' '}
                                    dari {stats.total} area
                                </Text>

                                {(search !== '' ||
                                    activeFilter !== 'semua') && (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        onClick={resetFilters}
                                        className="min-h-11 gap-2 px-3 text-sm"
                                    >
                                        Reset filter
                                    </Button>
                                )}
                            </div>
                        </section>
                    )}

                    {/* Empty states */}
                    {!hasAreas ? (
                        <EmptyState
                            icon={MapPin}
                            title="Belum ada stasi atau lingkungan yang dipublikasikan."
                            description="Daftar ini akan terisi ketika paroki menambahkan stasi atau lingkungan yang aktif."
                        />
                    ) : resultCount === 0 ? (
                        <EmptyState
                            icon={SearchX}
                            title="Tidak ada area yang cocok."
                            description="Coba kata kunci lain atau reset filter untuk melihat semua area."
                            action={
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={resetFilters}
                                >
                                    Tampilkan semua area
                                </Button>
                            }
                        />
                    ) : (
                        <div className="space-y-12">
                            {filteredSections.map((section) => (
                                <section
                                    key={section.type}
                                    aria-labelledby={`area-${section.type}`}
                                    className="space-y-5"
                                >
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="flex min-w-0 items-center gap-3">
                                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                                {section.type === 'stasi' ? (
                                                    <Church
                                                        className="size-5"
                                                        aria-hidden="true"
                                                    />
                                                ) : (
                                                    <UsersRound
                                                        className="size-5"
                                                        aria-hidden="true"
                                                    />
                                                )}
                                            </div>

                                            <div className="min-w-0 space-y-0.5">
                                                <Text
                                                    as="h2"
                                                    variant="h3"
                                                    id={`area-${section.type}`}
                                                >
                                                    {section.label}
                                                </Text>

                                                <Text
                                                    as="p"
                                                    variant="body-sm"
                                                    color="muted"
                                                >
                                                    {section.areas.length}{' '}
                                                    {section.label.toLocaleLowerCase(
                                                        'id-ID',
                                                    )}
                                                </Text>
                                            </div>
                                        </div>

                                        <ArrowRight
                                            className="size-4 shrink-0 text-muted-foreground"
                                            aria-hidden="true"
                                        />
                                    </div>

                                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {section.areas.map((area) => (
                                            <AreaCard
                                                key={area.id}
                                                area={area}
                                            />
                                        ))}
                                    </div>
                                </section>
                            ))}
                        </div>
                    )}
                </div>
            </PublicSection>
        </>
    );
}
