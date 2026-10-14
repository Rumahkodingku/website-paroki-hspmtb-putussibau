import { Link } from '@inertiajs/react';
import { Image as ImageIcon, Pencil } from 'lucide-react';
import { StatusBadge } from '@/components/list-parts';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { edit as editService } from '@/routes/services';
import type { ServiceListItem } from '../types';

export function ServiceCards({
    rows,
    methods,
    copy,
}: {
    rows: ServiceListItem[];
    methods: Record<string, string>;
    copy: Record<string, string>;
}) {
    if (rows.length === 0) {
        return null;
    }

    return (
        <ul className="space-y-3 md:hidden">
            {rows.map((service) => (
                <li
                    key={service.id}
                    className="rounded-lg border border-hairline p-4"
                >
                    <div className="flex items-start gap-3">
                        {service.thumbnail ? (
                            <img
                                src={service.thumbnail}
                                alt={service.thumbnail_alt ?? ''}
                                className="size-11 shrink-0 rounded-md border border-hairline object-cover"
                            />
                        ) : (
                            <span className="flex size-11 shrink-0 items-center justify-center rounded-md border border-hairline bg-canvas-soft text-muted-foreground">
                                <ImageIcon
                                    className="size-5"
                                    aria-hidden="true"
                                />
                            </span>
                        )}

                        <div className="min-w-0 flex-1 space-y-2">
                            <div className="flex items-start justify-between gap-3">
                                <div className="min-w-0">
                                    <Text
                                        as="p"
                                        variant="body-strong"
                                        className="truncate"
                                    >
                                        {service.title}
                                    </Text>
                                    <Text
                                        as="p"
                                        variant="caption"
                                        color="muted"
                                        className="truncate"
                                    >
                                        {service.category?.name ?? service.slug}
                                    </Text>
                                </div>

                                <StatusBadge active={service.is_active} />
                            </div>

                            <div className="flex flex-wrap items-center gap-2">
                                <Badge variant="outline">
                                    {methods[service.registration_method]}
                                </Badge>

                                {service.is_emergency ? (
                                    <Badge variant="secondary">
                                        {copy.emergency_badge}
                                    </Badge>
                                ) : null}

                                {!service.is_active ? (
                                    <Badge variant="secondary">
                                        {copy.needs_validation}
                                    </Badge>
                                ) : null}
                            </div>

                            {service.can.update ? (
                                <Link
                                    href={editService(service.id)}
                                    className="inline-flex items-center gap-1.5 text-sm text-primary"
                                >
                                    <Pencil
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    {copy.edit_action}
                                </Link>
                            ) : null}
                        </div>
                    </div>
                </li>
            ))}
        </ul>
    );
}
