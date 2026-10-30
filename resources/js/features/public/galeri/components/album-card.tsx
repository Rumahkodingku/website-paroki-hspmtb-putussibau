import { Link } from '@inertiajs/react';
import Image from '@/components/image';
import { ArrowRight, Images } from 'lucide-react';
import { Text } from '@/components/text';
import { show as galleryShow } from '@/routes/galeri';
import type { PublicGalleryCard as PublicGalleryCardData } from '../types';

export function AlbumCard({ album }: { album: PublicGalleryCardData }) {
    return (
        <article className="group relative overflow-hidden rounded-lg border border-border transition-colors focus-within:border-primary-on-surface hover:border-primary-on-surface">
            {album.cover_url ? (
                <Image
                    src={album.cover_url}
                    alt=""
                    loading="lazy"
                    aspectRatio="4 / 3"
                    fit="cover"
                    className="w-full rounded-t-lg"
                />
            ) : (
                <div className="flex aspect-4/3 w-full items-center justify-center rounded-t-lg bg-canvas-subtle text-muted-foreground">
                    <Images className="size-8" aria-hidden="true" />
                </div>
            )}

            <div className="space-y-2 p-6">
                <Text as="h3" variant="h3">
                    {album.title}
                </Text>

                {album.event_date ? (
                    <Text as="p" variant="body-sm" color="muted">
                        {new Intl.DateTimeFormat('id-ID', {
                            day: 'numeric',
                            month: 'long',
                            year: 'numeric',
                        }).format(new Date(album.event_date))}
                    </Text>
                ) : null}

                <Text as="p" variant="body-sm" color="muted">
                    {album.photo_count} foto
                </Text>

                <span
                    aria-hidden="true"
                    className="inline-flex items-center gap-2 text-sm text-primary-on-surface transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                >
                    Lihat Album
                    <ArrowRight className="size-4" />
                </span>
            </div>

            <Link
                href={galleryShow(album.slug)}
                prefetch
                className="absolute inset-0 rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <span className="sr-only">Lihat album {album.title}</span>
            </Link>
        </article>
    );
}
