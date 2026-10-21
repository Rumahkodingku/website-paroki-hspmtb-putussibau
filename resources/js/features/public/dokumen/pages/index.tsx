import { useState, type FormEvent } from 'react';
import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { ListPagination } from '@/components/list-pagination';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { index as documentsIndex } from '@/routes/dokumen';
import { DocumentCard } from '@/components/document-card';
import type {
    PublicDocumentCard,
    PublicDocumentCategory,
    PublicDocumentFilters,
} from '../types';
import type { Paginated } from '@/types';

type Props = {
    documents: Paginated<PublicDocumentCard>;
    featured: PublicDocumentCard[];
    categories: PublicDocumentCategory[];
    filters: PublicDocumentFilters;
};

export default function DokumenIndex({
    documents,
    featured,
    categories,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.q);
    const isFiltered = filters.q !== '' || filters.kategori !== '';

    const applyFilters = (next: Partial<PublicDocumentFilters>) => {
        router.get(
            documentsIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const submitSearch = (event: FormEvent) => {
        event.preventDefault();
        applyFilters({ q: search });
    };

    return (
        <>
            <Seo
                title="Dokumen Paroki"
                description="Dokumen resmi, formulir, pedoman, dan informasi yang dibagikan Paroki HSPMTB Putussibau."
            />

            <Head title="Dokumen" />

            <PublicSection>
                <div className="space-y-10">
                    <SectionHeading
                        eyebrow="Dokumen"
                        title="Dokumen Paroki"
                        description="Formulir, pedoman, pengumuman resmi, dan dokumen pelayanan yang dapat diunduh."
                    />

                    <div className="flex flex-wrap items-end gap-3">
                        <form
                            onSubmit={submitSearch}
                            className="flex min-w-56 flex-1 items-end gap-2"
                        >
                            <label
                                htmlFor="document-search"
                                className="sr-only"
                            >
                                Cari dokumen
                            </label>
                            <input
                                id="document-search"
                                type="search"
                                value={search}
                                onChange={(event: any) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Cari judul atau deskripsi dokumen"
                                className="h-11 min-w-0 flex-1 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
                            />
                            <Button type="submit" variant="outline">
                                <Search className="size-4" aria-hidden="true" />
                                Cari
                            </Button>
                        </form>

                        <div className="flex items-end gap-2">
                            <label
                                htmlFor="document-category"
                                className="sr-only"
                            >
                                Filter kategori
                            </label>
                            <select
                                id="document-category"
                                value={filters.kategori}
                                onChange={(event: any) =>
                                    applyFilters({
                                        kategori: event.target.value,
                                    })
                                }
                                className="h-11 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
                            >
                                <option value="">Semua kategori</option>
                                {categories.map((category) => (
                                    <option
                                        key={category.id}
                                        value={category.slug}
                                    >
                                        {category.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                    </div>

                    {featured.length > 0 ? (
                        <section aria-labelledby="featured-documents">
                            <Text as="h2" variant="h3" id="featured-documents">
                                Dokumen Unggulan
                            </Text>
                            <div className="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {featured.map((document) => (
                                    <DocumentCard
                                        key={`featured-${document.id}`}
                                        document={document}
                                    />
                                ))}
                            </div>
                        </section>
                    ) : null}

                    <section aria-labelledby="all-documents">
                        <Text as="h2" variant="h3" id="all-documents">
                            Semua Dokumen
                        </Text>

                        {documents.data.length > 0 ? (
                            <div className="mt-5 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {documents.data.map((document) => (
                                    <DocumentCard
                                        key={document.id}
                                        document={document}
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="mt-5 rounded-lg border border-dashed border-border p-10 text-center">
                                <Text as="p" variant="heading-sm">
                                    {isFiltered
                                        ? 'Tidak ada dokumen yang cocok dengan filter.'
                                        : 'Belum ada dokumen yang dipublikasikan.'}
                                </Text>
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="mt-1"
                                >
                                    {isFiltered
                                        ? 'Coba kata kunci lain atau reset filter.'
                                        : 'Dokumen akan muncul di sini setelah dipublikasikan.'}
                                </Text>
                                {isFiltered ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="mt-4"
                                        onClick={() => {
                                            setSearch('');
                                            router.get(
                                                documentsIndex().url,
                                                {},
                                                { replace: true },
                                            );
                                        }}
                                    >
                                        Reset filter
                                    </Button>
                                ) : null}
                            </div>
                        )}

                        <div className="mt-8">
                            <ListPagination
                                meta={documents.meta}
                                previous={documents.links.prev}
                                next={documents.links.next}
                            />
                        </div>
                    </section>
                </div>
            </PublicSection>
        </>
    );
}
