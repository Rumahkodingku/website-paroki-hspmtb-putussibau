import { Head } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { ListPagination } from '@/components/list-pagination';
import { CommunityCard } from '../components/community-card';
import { CommunityFilters } from '../components/community-filters';
import type {
    PublicCommunityCard as PublicCommunityCardData,
    PublicCommunityCategory,
    PublicCommunityFilters,
} from '../types';
import type { Paginated } from '@/types';

type Props = {
    communities: Paginated<PublicCommunityCardData>;
    categories: PublicCommunityCategory[];
    filters: PublicCommunityFilters;
};

/**
 * The directory.
 *
 * DESIGN.md asks whitespace to do the work, so the grid is cards on the page
 * background with one heading and one filter row above it, not a stack of
 * rounded containers.
 */
export default function KomunitasIndex({
    communities,
    categories,
    filters,
}: Props) {
    const isFiltered = filters.q !== '' || filters.kategori !== '';

    return (
        <>
            <Seo
                title="Komunitas"
                description="Direktori komunitas dan kelompok kategorial Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
            />

            <Head title="Komunitas" />

            <PublicSection>
                <div className="space-y-10">
                    <SectionHeading
                        eyebrow="Komunitas"
                        title="Komunitas Paroki"
                        description="Temukan kelompok dan kategorial di paroki."
                    />

                    <CommunityFilters
                        filters={filters}
                        categories={categories}
                        resultCount={communities.meta.total}
                    />

                    {communities.data.length > 0 ? (
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {communities.data.map((community) => (
                                <CommunityCard
                                    key={community.id}
                                    community={community}
                                />
                            ))}
                        </div>
                    ) : (
                        <div className="rounded-lg border border-dashed border-hairline p-10 text-center">
                            <Text as="p" variant="body-strong">
                                {isFiltered
                                    ? 'Tidak ada komunitas untuk pencarian ini.'
                                    : 'Belum ada komunitas yang dipublikasikan.'}
                            </Text>
                            <Text
                                as="p"
                                variant="caption"
                                color="muted"
                                className="mt-1"
                            >
                                {isFiltered
                                    ? 'Coba kata lain atau reset filter.'
                                    : 'Komunitas paroki akan muncul di sini setelah dipublikasikan.'}
                            </Text>
                        </div>
                    )}

                    {communities.meta.last_page > 1 ? (
                        <ListPagination
                            meta={communities.meta}
                            previous={communities.links.prev}
                            next={communities.links.next}
                        />
                    ) : null}
                </div>
            </PublicSection>
        </>
    );
}
