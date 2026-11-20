import { Link } from '@inertiajs/react';
import { ArrowRight, Church, UsersRound } from 'lucide-react';

import ParishImage from '@/components/parish-image';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { show as areaShow } from '@/routes/area';

import type { PublicAreaCard as PublicAreaCardData } from '../types';

export function AreaCard({ area }: { area: PublicAreaCardData }) {
    const isStasi = area.type === 'stasi';

    return (
        <article className="group relative flex h-full flex-col overflow-hidden rounded-2xl border border-border bg-card transition-all duration-300">
            <div className="relative aspect-video overflow-hidden bg-muted">
                {area.photoUrl ? (
                    <ParishImage
                        src={area.photoUrl}
                        alt={`Foto ${area.name}`}
                        width={640}
                        height={360}
                        aspectRatio="16 / 9"
                        fit="cover"
                        className="h-full w-full transition-transform duration-500 motion-reduce:transform-none motion-reduce:transition-none"
                    />
                ) : (
                    <div
                        className="flex h-full w-full items-center justify-center bg-linear-to-br from-primary/5 via-muted to-primary/10"
                        aria-hidden="true"
                    >
                        <div className="flex size-16 items-center justify-center rounded-2xl border border-border/60 bg-background/80 text-primary shadow-sm">
                            {isStasi ? (
                                <Church className="size-8" />
                            ) : (
                                <UsersRound className="size-8" />
                            )}
                        </div>
                    </div>
                )}
            </div>

            <div className="flex flex-1 flex-col gap-3 p-4 sm:p-5">
                <div>
                    <Badge
                        variant="secondary"
                        className="rounded-full px-2.5 py-1 text-xs font-medium"
                    >
                        {area.type_label}
                    </Badge>
                </div>

                <Text
                    as="h3"
                    variant="h3"
                    className="line-clamp-2 min-h-14 text-base leading-6 font-semibold tracking-tight transition-colors group-hover:text-primary sm:text-lg"
                >
                    {area.name}
                </Text>

                {area.leader_name ? (
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="line-clamp-1"
                    >
                        Ketua: {area.leader_name}
                    </Text>
                ) : null}

                <div className="mt-auto flex items-center justify-between border-t border-border/70 pt-3">
                    <span className="text-sm font-medium text-primary">
                        Lihat Profil
                    </span>

                    <span
                        className="flex size-8 items-center justify-center rounded-full bg-primary/5 text-primary transition-all duration-300 group-hover:bg-primary group-hover:text-primary-foreground motion-reduce:transition-none"
                        aria-hidden="true"
                    >
                        <ArrowRight className="size-4 transition-transform duration-300 group-hover:translate-x-0.5 motion-reduce:transform-none motion-reduce:transition-none" />
                    </span>
                </div>
            </div>

            {/* Full-card navigation */}
            <Link
                href={areaShow(area.slug)}
                prefetch
                className="absolute inset-0 z-10 rounded-2xl focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                aria-label={`Lihat profil ${area.type_label} ${area.name}`}
            />
        </article>
    );
}
