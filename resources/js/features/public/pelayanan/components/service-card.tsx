import { Link } from '@inertiajs/react';
import { ArrowRight, HeartPulse, Image as ImageIcon } from 'lucide-react';
import Image from '@/components/image';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { show } from '@/routes/pelayanan';
import type { PublicServiceCard as PublicServiceCardData } from '../types';

type Props = {
    service: PublicServiceCardData;
};

export function PublicServiceCard({ service }: Props) {
    const isEmergency = service.is_emergency;

    return (
        <Link
            href={show({ service: service.slug })}
            aria-label={`${service.title} — lihat persyaratan dan informasi layanan`}
            className={[
                'group relative flex min-w-0 items-center gap-4',
                'rounded-lg border-b border-border/70',
                'px-3 py-4 transition-colors duration-200',
                'sm:gap-5 sm:px-4 sm:py-5',
                'lg:gap-6 lg:px-5 lg:py-6',
                'focus-visible:ring-2 focus-visible:outline-none',
                'focus-visible:ring-ring focus-visible:ring-offset-2',
                isEmergency ? 'bg-destructive/5' : 'hover:bg-muted/40',
            ].join(' ')}
        >
            {/* Service thumbnail */}
            <div
                className={[
                    'relative aspect-4/3 w-24 shrink-0',
                    'overflow-hidden rounded-md bg-muted',
                    'sm:w-32 lg:w-40',
                    isEmergency ? 'ring-1 ring-destructive/20' : '',
                ].join(' ')}
            >
                {service.thumbnail ? (
                    <Image
                        src={service.thumbnail}
                        alt={service.thumbnail_alt ?? ''}
                        loading="lazy"
                        decoding="async"
                        aspectRatio="4 / 3"
                        fit="cover"
                        imageClassName={[
                            'transition-transform duration-300',
                            'group-hover:scale-105',
                        ].join(' ')}
                        className="size-full"
                    />
                ) : (
                    <div className="flex size-full items-center justify-center text-muted-foreground">
                        {isEmergency ? (
                            <HeartPulse
                                className="size-7 sm:size-8"
                                aria-hidden="true"
                            />
                        ) : (
                            <ImageIcon
                                className="size-7 sm:size-8"
                                aria-hidden="true"
                            />
                        )}
                    </div>
                )}
            </div>

            {/* Service information */}
            <div className="flex min-w-0 flex-1 flex-col items-start">
                {isEmergency ? (
                    <Badge
                        variant="secondary"
                        className="mb-2 gap-1.5 border border-destructive/15 bg-destructive/10 text-destructive"
                    >
                        <HeartPulse className="size-3.5" aria-hidden="true" />
                        Pelayanan darurat
                    </Badge>
                ) : null}

                <Text
                    as="h3"
                    variant="h3"
                    className={[
                        'text-sm leading-snug font-semibold',
                        'transition-colors duration-200',
                        'group-hover:text-primary-on-surface',
                        'sm:text-base lg:text-lg',
                        isEmergency ? 'text-destructive' : '',
                    ].join(' ')}
                >
                    {service.title}
                </Text>

                {service.summary ? (
                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        className={[
                            'mt-1.5 line-clamp-2 text-xs leading-relaxed',
                            'sm:mt-2 sm:text-sm',
                        ].join(' ')}
                    >
                        {service.summary}
                    </Text>
                ) : null}

                <span
                    className={[
                        'mt-2 inline-flex items-center gap-1.5',
                        'text-xs font-semibold sm:mt-3 sm:text-sm',
                        isEmergency
                            ? 'text-destructive'
                            : 'text-primary-on-surface',
                    ].join(' ')}
                >
                    {isEmergency
                        ? 'Lihat informasi layanan'
                        : 'Lihat persyaratan'}

                    <ArrowRight
                        className="size-3.5 shrink-0 transition-transform duration-200 group-hover:translate-x-1 sm:size-4"
                        aria-hidden="true"
                    />
                </span>
            </div>
        </Link>
    );
}
