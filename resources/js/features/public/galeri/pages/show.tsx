import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as galeriIndex } from '@/routes/galeri';
import { Lightbox } from '../components/lightbox';
import { PhotoGrid } from '../components/photo-grid';
import type { PublicGalleryDetail } from '../types';
import { formatWibDate } from '@/lib/datetime';

type Props = {
    album: PublicGalleryDetail;
};

/**
 * One album.
 *
 * The open photo is the only piece of state, and it lives here rather than in
 * the lightbox so a single source of truth decides which photo is shown. The
 * lightbox owns nothing but the document keyboard listener.
 *
 * Only published albums reach this page: the route binds {gallery:slug} through
 * GalleryAlbum::published(), so a draft 404s before this component runs.
 */
export default function GaleriShow({ album }: Props) {
    const [openIndex, setOpenIndex] = useState<number | null>(null);

    const hasPhotos = album.photos.length > 0;

    return (
        <>
            <Seo
                title={album.title}
                description={album.description ?? undefined}
                ogImage={album.cover_url ?? undefined}
            />

            <Head title={album.title} />

            <PublicSection>
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Galeri', href: galeriIndex() },
                        { title: album.title },
                    ]}
                />

                <SectionHeading
                    eyebrow="Album"
                    title={album.title}
                    description={album.description ?? undefined}
                />

                {album.event_date || album.location ? (
                    <div className="mt-4 flex flex-wrap items-center gap-x-6 gap-y-1">
                        {album.event_date ? (
                            <Text as="p" variant="body-sm" color="muted">
                                {formatWibDate(album.event_date)}
                            </Text>
                        ) : null}
                        {album.location ? (
                            <Text as="p" variant="body-sm" color="muted">
                                {album.location}
                            </Text>
                        ) : null}
                    </div>
                ) : null}
            </PublicSection>

            <PublicSection>
                {hasPhotos ? (
                    <PhotoGrid photos={album.photos} onOpen={setOpenIndex} />
                ) : (
                    <div className="rounded-lg border border-dashed border-border p-10 text-center">
                        <Text as="p" variant="heading-sm">
                            Album ini belum memiliki foto.
                        </Text>
                    </div>
                )}

                <Button asChild variant="ghost" className="mt-8">
                    <Link href={galeriIndex()}>
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Kembali ke galeri
                    </Link>
                </Button>
            </PublicSection>

            <Lightbox
                photos={album.photos}
                index={openIndex}
                title={album.title}
                onClose={() => setOpenIndex(null)}
                onNavigate={setOpenIndex}
            />
        </>
    );
}
