import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { show as areaShow } from '@/routes/area';
import type { PublicAreaCard as PublicAreaCardData } from '../types';

/**
 * A directory card for one area.
 *
 * The whole card is one link via a stretched pseudo-element, so the heading
 * keeps its semantics and the card is one tab stop.
 */
export function AreaCard({ area }: { area: PublicAreaCardData }) {
    return (
        <article className="group relative overflow-hidden rounded-lg border border-border p-6 transition-colors focus-within:border-primary-on-surface hover:border-primary-on-surface">
            <div className="space-y-3">
                <Badge variant="secondary">{area.type_label}</Badge>

                <Text as="h3" variant="h3">
                    {area.name}
                </Text>

                {area.leader_name ? (
                    <Text as="p" variant="body-sm" color="muted">
                        Ketua: {area.leader_name}
                    </Text>
                ) : null}

                <span
                    aria-hidden="true"
                    className="inline-flex items-center gap-2 text-sm text-primary-on-surface transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                >
                    Lihat Detail
                    <ArrowRight className="size-4" />
                </span>
            </div>

            <Link
                href={areaShow(area.slug)}
                prefetch
                className="absolute inset-0 rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <span className="sr-only">
                    Lihat detail {area.type_label} {area.name}
                </span>
            </Link>
        </article>
    );
}
