import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Text } from '@/components/text';
import { formatWibDateTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';
import { contentEditHref, contentTypeLabel } from '../content-target';
import type { RecentContentItem } from '../types';
import { ContentStatusBadge } from './content-status-badge';

export function RecentContentRow({
    item,
    displayTimezone,
}: {
    item: RecentContentItem;
    displayTimezone: string;
}) {
    return (
        <li key={`${item.type}-${item.id}`}>
            <Link
                href={contentEditHref(item.type, item.id)}
                className={cn(
                    'group grid min-w-0 gap-2 px-4 py-3.5',
                    'transition-colors hover:bg-muted/40',
                    'focus-visible:relative focus-visible:z-10',
                    'focus-visible:ring-2 focus-visible:outline-none',
                    'focus-visible:ring-ring focus-visible:ring-inset',
                    'sm:px-5',
                    'lg:grid-cols-[8rem_minmax(0,1fr)_auto_12rem]',
                    'lg:items-center lg:gap-4 lg:py-4',
                )}
            >
                <Text
                    as="span"
                    variant="fine-print"
                    color="muted"
                    className="leading-5"
                >
                    {contentTypeLabel(item.type)}
                </Text>

                <span className="flex min-w-0 items-center gap-2">
                    <Text
                        as="span"
                        variant="label"
                        lineClamp={2}
                        className={cn(
                            'min-w-0 flex-1 font-medium',
                            'leading-snug text-foreground',
                            'transition-colors group-hover:text-primary',
                        )}
                    >
                        {item.title}
                    </Text>

                    <ArrowRight
                        className={cn(
                            'hidden size-4 shrink-0',
                            'text-muted-foreground/50',
                            'transition-all group-hover:translate-x-0.5',
                            'group-hover:text-primary',
                            'lg:block',
                        )}
                        aria-hidden="true"
                    />
                </span>

                <span className="flex min-w-0 flex-wrap items-center gap-x-3 gap-y-2 lg:contents">
                    <span className="justify-self-start">
                        <ContentStatusBadge
                            status={item.status}
                            label={item.status_label}
                            isPublished={item.is_published}
                        />
                    </span>

                    <Text
                        as="span"
                        variant="fine-print"
                        color="muted"
                        className="min-w-0 leading-5 tabular-nums lg:justify-self-start"
                    >
                        {formatWibDateTime(item.updated_at, displayTimezone)}
                    </Text>
                </span>
            </Link>
        </li>
    );
}
