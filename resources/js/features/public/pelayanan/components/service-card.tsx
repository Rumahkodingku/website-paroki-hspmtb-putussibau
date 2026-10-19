import { Link } from '@inertiajs/react';
import { Image as ImageIcon } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/pelayanan';
import type { PublicServiceCard as PublicServiceCardData } from '../types';

/**
 * One service on the index, and in the "Layanan Lainnya" strip.
 *
 * A link whose whole surface is the target, because a card with a small "lihat"
 * link in the corner is a card that is harder to tap on a phone than it needs to
 * be (NFR-RESP). The thumbnail is optional — a service without one shows a
 * neutral placeholder rather than a broken image.
 */
export function PublicServiceCard({
    service,
}: {
    service: PublicServiceCardData;
}) {
    return (
        <Link
            href={show({ service: service.slug })}
            className="group flex h-full flex-col overflow-hidden rounded-lg border border-border bg-card transition-colors hover:border-primary-on-surface/40 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            {service.thumbnail ? (
                <img
                    src={service.thumbnail}
                    alt={service.thumbnail_alt ?? ''}
                    loading="lazy"
                    className="aspect-video w-full object-cover"
                />
            ) : (
                <div className="flex aspect-video w-full items-center justify-center bg-canvas-subtle text-muted-foreground">
                    <ImageIcon className="size-8" aria-hidden="true" />
                </div>
            )}

            <div className="flex flex-1 flex-col gap-3 p-6">
                {service.is_emergency ? (
                    <div>
                        <Badge variant="secondary">Darurat</Badge>
                    </div>
                ) : null}

                <Text
                    as="h3"
                    variant="h3"
                    className="group-hover:text-primary-on-surface"
                >
                    {service.title}
                </Text>

                {service.summary ? (
                    <Text as="p" variant="body" color="muted">
                        {service.summary}
                    </Text>
                ) : null}
            </div>
        </Link>
    );
}
