import { useState } from 'react';
import { Head } from '@inertiajs/react';
import { MapPin, SearchX } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { AreaCard } from '../components/area-card';
import { AreaSearch } from '../components/area-search';
import type { PublicAreaSection } from '../types';

type Props = {
    sections: PublicAreaSection[];
    hasAreas: boolean;
};

/**
 * The public directory.
 *
 * The list is small and already grouped by type on the server, so search is a
 * client-side name filter rather than a round trip: there is no pagination and
 * nothing to defer.
 */
export default function StasiLingkunganIndex({ sections, hasAreas }: Props) {
    const [search, setSearch] = useState('');

    const query = search.trim().toLowerCase();

    const filtered = sections.map((section) => ({
        ...section,
        areas: section.areas.filter(
            (area) => query === '' || area.name.toLowerCase().includes(query),
        ),
    }));

    const total = filtered.reduce(
        (sum, section) => sum + section.areas.length,
        0,
    );

    return (
        <>
            <Seo
                title="Area"
                description="Daftar stasi, wilayah, dan lingkungan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
            />

            <Head title="Area" />

            <PublicSection>
                <div className="space-y-10">
                    <SectionHeading
                        eyebrow="Area"
                        title="Stasi & Lingkungan"
                        description="Daftar stasi, wilayah, dan lingkungan di lingkungan paroki."
                    />

                    {hasAreas ? (
                        <AreaSearch
                            value={search}
                            onChange={setSearch}
                            resultCount={total}
                        />
                    ) : null}

                    {/*
                        Two different empty states, not one: an empty parish is
                        a different fact from a search that missed. EmptyState
                        already carries the heading level, the icon frame and
                        the muted description, so neither needs a local box.
                    */}
                    {!hasAreas ? (
                        <EmptyState
                            icon={MapPin}
                            title="Belum ada stasi atau lingkungan yang dipublikasikan."
                            description="Daftar ini terisi sendiri begitu paroki menambahkan stasi atau lingkungan aktif."
                        />
                    ) : total === 0 ? (
                        <EmptyState
                            icon={SearchX}
                            title="Tidak ada area yang cocok dengan pencarian."
                            description="Coba kata lain atau kosongkan kotak pencarian."
                            action={
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setSearch('')}
                                >
                                    Reset pencarian
                                </Button>
                            }
                        />
                    ) : (
                        <div className="space-y-12">
                            {filtered.map((section) =>
                                section.areas.length > 0 ? (
                                    <section
                                        key={section.type}
                                        aria-labelledby={`area-${section.type}`}
                                        className="space-y-6"
                                    >
                                        <Text
                                            as="h2"
                                            variant="h3"
                                            id={`area-${section.type}`}
                                        >
                                            {section.label}
                                        </Text>

                                        <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                                            {section.areas.map((area) => (
                                                <AreaCard
                                                    key={area.id}
                                                    area={area}
                                                />
                                            ))}
                                        </div>
                                    </section>
                                ) : null,
                            )}
                        </div>
                    )}
                </div>
            </PublicSection>
        </>
    );
}
