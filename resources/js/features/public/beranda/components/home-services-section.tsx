import { Link } from '@inertiajs/react';
import { ArrowRight, HeartHandshake } from 'lucide-react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as pelayanan, show as serviceShow } from '@/routes/pelayanan';
import type { HomeService } from '../types';

/**
 * The active pastoral services.
 *
 * These are shortcuts, not a second directory. A divided list keeps the six
 * services scannable without turning the homepage into another card grid, and
 * the section only renders when the parish has activated at least one service.
 */
export function HomeServicesSection({
    services,
    enabled,
}: {
    services: HomeService[];
    enabled: boolean;
}) {
    if (!enabled || services.length === 0) {
        return null;
    }

    return (
        <PublicSection>
            <div className="space-y-8">
                <SectionHeading
                    title="Pelayanan Sakramen"
                    description="Pelayanan yang dapat dihubungi atau didaftarkan melalui paroki."
                    action={
                        <Button asChild variant="outline">
                            <Link href={pelayanan()}>
                                Semua Pelayanan
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    }
                />

                <ul className="grid gap-x-12 md:grid-cols-2">
                    {services.map((service) => (
                        <li key={service.id}>
                            <Link
                                href={serviceShow({ service: service.slug })}
                                className="group flex min-h-20 items-start gap-4 border-t border-border py-5 transition-colors hover:border-primary-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                            >
                                {service.thumbnail ? (
                                    <span className="mt-0.5 block size-12 shrink-0 overflow-hidden rounded-lg bg-canvas-subtle">
                                        <img
                                            src={service.thumbnail}
                                            alt={service.thumbnail_alt ?? ''}
                                            srcSet={service.thumbnail_srcset}
                                            sizes={service.thumbnail_sizes}
                                            loading="lazy"
                                            className="size-full object-cover"
                                        />
                                    </span>
                                ) : (
                                    <span className="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-full bg-primary-soft text-primary-on-surface">
                                        <HeartHandshake
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </span>
                                )}

                                <span className="min-w-0 flex-1">
                                    <span className="flex flex-wrap items-center gap-2">
                                        <Text
                                            as="span"
                                            variant="heading-sm"
                                            className="group-hover:text-primary-on-surface"
                                        >
                                            {service.title}
                                        </Text>
                                        {service.is_emergency ? (
                                            <Badge variant="secondary">
                                                Darurat
                                            </Badge>
                                        ) : null}
                                        {service.thumbnail_is_illustration ? (
                                            <Badge variant="outline">
                                                Ilustrasi
                                            </Badge>
                                        ) : null}
                                    </span>

                                    {service.summary ? (
                                        <Text
                                            as="span"
                                            variant="body-sm"
                                            color="muted"
                                            className="mt-1 block"
                                        >
                                            {service.summary}
                                        </Text>
                                    ) : null}
                                </span>

                                <ArrowRight
                                    className="mt-1 size-4 shrink-0 text-subtle transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                                    aria-hidden="true"
                                />
                            </Link>
                        </li>
                    ))}
                </ul>
            </div>
        </PublicSection>
    );
}

export default HomeServicesSection;
