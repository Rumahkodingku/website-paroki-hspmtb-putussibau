import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import {
    MasonryGrid,
    MasonryItem,
    aspectRatioFrom,
} from '@/components/masonry-grid';
import { ParishImage } from '@/components/parish-image';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Button } from '@/components/ui/button';
import { index as galeri } from '@/routes/galeri';
import { show as galleryShow } from '@/routes/galeri';
import type { HomeGalleryPhoto, HomeIllustration } from '../types';

/**
 * A short photographic strip from the published albums.
 *
 * The photos are the content, so there is no card chrome: a border radius and
 * the photograph itself carry the block. Every image links back to the album it
 * belongs to, which is the only useful destination from a teaser.
 *
 * The strip is a masonry grid (D-75): each photo keeps its own aspect ratio
 * instead of being cropped to a fixed 4:3, because a strip of identically
 * cropped tiles is not a masonry grid and the crop decides what survives of a
 * portrait. The ratio comes from the media row, so the height is reserved
 * before a lazy image arrives and the section does not jump.
 *
 * When the gallery is empty and the local illustration feature is on, a clearly
 * labelled illustration strip is shown instead. It has no album links and never
 * pretends to be parish photography (D-71).
 */
export function HomeGallerySection({
    galleryPhotos,
    galleryIllustrations,
    enabled,
}: {
    galleryPhotos: HomeGalleryPhoto[];
    galleryIllustrations: HomeIllustration[];
    enabled: boolean;
}) {
    const photos = galleryPhotos.filter(
        (photo) => photo.url !== null || photo.thumb_url !== null,
    );

    if (!enabled) {
        return null;
    }

    const hasPhotos = photos.length > 0;
    const hasIllustrations = !hasPhotos && galleryIllustrations.length > 0;

    if (!hasPhotos && !hasIllustrations) {
        return null;
    }

    return (
        <PublicSection surface="soft">
            <div className="space-y-8">
                <SectionHeading
                    title={hasPhotos ? 'Galeri Kegiatan' : 'Ilustrasi'}
                    description={
                        hasPhotos
                            ? 'Potret kehidupan dan kegiatan umat paroki.'
                            : 'Belum ada album terbit. Gambar berikut adalah ilustrasi, bukan foto paroki.'
                    }
                    action={
                        <Button asChild variant="outline">
                            <Link href={galeri()}>
                                Semua Galeri
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    }
                />

                {hasPhotos ? (
                    <MasonryGrid className="md:columns-3">
                        {photos.map((photo) => {
                            const srcSet = [
                                photo.thumb_url
                                    ? `${photo.thumb_url} 400w`
                                    : null,
                                photo.url ? `${photo.url} 960w` : null,
                            ]
                                .filter(Boolean)
                                .join(', ');

                            return (
                                <MasonryItem key={photo.id}>
                                    <Link
                                        href={galleryShow(photo.album.slug)}
                                        prefetch
                                        className="group block overflow-hidden rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-canvas-subtle focus-visible:outline-none"
                                    >
                                        <ParishImage
                                            src={
                                                photo.url ??
                                                photo.thumb_url ??
                                                ''
                                            }
                                            alt={
                                                photo.alt_text ??
                                                photo.caption ??
                                                ''
                                            }
                                            width={photo.width ?? 1200}
                                            height={photo.height ?? 900}
                                            srcSet={srcSet || undefined}
                                            sizes="(min-width: 768px) 31vw, 46vw"
                                            aspectRatio={aspectRatioFrom(
                                                photo.width,
                                                photo.height,
                                            )}
                                            fit="contain"
                                            className="transition-transform duration-300 group-hover:scale-[1.02] motion-reduce:transform-none motion-reduce:transition-none"
                                        />
                                        <span className="sr-only">
                                            Lihat album {photo.album.title}
                                        </span>
                                    </Link>
                                </MasonryItem>
                            );
                        })}
                    </MasonryGrid>
                ) : (
                    <MasonryGrid className="md:columns-3">
                        {galleryIllustrations.map((illustration) => (
                            <MasonryItem
                                key={illustration.id}
                                className="relative overflow-hidden rounded-lg"
                            >
                                <ParishImage
                                    src={illustration.src}
                                    alt={illustration.alt}
                                    width={illustration.width}
                                    height={illustration.height}
                                    srcSet={illustration.srcSet}
                                    sizes={illustration.sizes}
                                    aspectRatio={aspectRatioFrom(
                                        illustration.width,
                                        illustration.height,
                                    )}
                                    fit="contain"
                                />
                                <span className="absolute top-3 left-3 rounded-pill bg-gold-soft px-2.5 py-1 text-fine-print font-medium text-navy-deep">
                                    Ilustrasi
                                </span>
                            </MasonryItem>
                        ))}
                    </MasonryGrid>
                )}
            </div>
        </PublicSection>
    );
}

export default HomeGallerySection;
