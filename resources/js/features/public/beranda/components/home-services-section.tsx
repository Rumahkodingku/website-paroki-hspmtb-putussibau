import { Link } from '@inertiajs/react';
import { ArrowRight, ArrowUpRight } from 'lucide-react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as pelayanan, show as serviceShow } from '@/routes/pelayanan';
import type { HomeService } from '../types';

function ServiceItem({ service }: { service: HomeService }) {
    return (
        <li className="min-w-0 border-t border-border">
            <Link
                href={serviceShow({ service: service.slug })}
                className="group flex min-h-24 items-start justify-between gap-4 py-5 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
            >
                <span className="min-w-0 flex-1">
                    <span className="flex flex-wrap items-center gap-x-2 gap-y-1">
                        <Text
                            as="span"
                            variant="heading-sm"
                            className="text-foreground transition-colors group-hover:text-primary"
                        >
                            {service.title}
                        </Text>

                        {service.is_emergency ? (
                            <Badge variant="destructive">Darurat</Badge>
                        ) : null}

                        {service.thumbnail_is_illustration ? (
                            <Badge variant="outline">Ilustrasi</Badge>
                        ) : null}
                    </span>

                    {service.summary ? (
                        <Text
                            as="span"
                            variant="body-sm"
                            color="muted"
                            className="mt-1.5 block"
                        >
                            {service.summary}
                        </Text>
                    ) : null}
                </span>

                <ArrowUpRight
                    className={`mt-0.5 size-5 shrink-0 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transform-none motion-reduce:transition-none ${
                        service.is_emergency
                            ? 'text-primary'
                            : 'text-muted-foreground'
                    }`}
                    aria-hidden="true"
                />
            </Link>
        </li>
    );
}

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
                    eyebrow="Pelayanan Sakramen"
                    title="Pelayanan Sakramen"
                    description="Informasi pelayanan dan sakramen yang dapat dihubungi atau didaftarkan melalui paroki."
                    action={
                        <Button
                            asChild
                            variant="outline"
                            className="group h-11 rounded-full border-primary/70 bg-transparent px-5 text-sm text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
                        >
                            <Link href={pelayanan()}>
                                Semua Pelayanan
                                <ArrowRight
                                    aria-hidden="true"
                                    className="size-4 transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                                />
                            </Link>
                        </Button>
                    }
                />

                <ul className="grid grid-cols-1 gap-x-12 md:grid-cols-2">
                    {services.map((service) => (
                        <ServiceItem key={service.id} service={service} />
                    ))}
                </ul>
            </div>
        </PublicSection>
    );
}

export default HomeServicesSection;
