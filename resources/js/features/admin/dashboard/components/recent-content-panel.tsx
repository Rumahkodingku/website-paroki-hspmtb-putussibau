import { Link } from '@inertiajs/react';
import { FileText } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Text } from '@/components/text';
import { formatWibDateTime } from '@/lib/datetime';
import { cn } from '@/lib/utils';
import { contentEditHref, contentTypeLabel } from '../content-target';
import type { RecentContentItem } from '../types';
import { ContentStatusBadge } from './content-status-badge';
import { Panel } from './panel';

/*
 * Konten Terbaru: the newest rows across articles, activities and documents.
 *
 * The panel is named for what it holds. `updated_at` says when a row last
 * changed — never who changed it, because the app has no audit log and this is
 * not pretending to be one (roadmap 9B section 6.10).
 */
export function RecentContentPanel({
    items,
    displayTimezone,
    className,
}: {
    items: RecentContentItem[];
    displayTimezone: string;
    className?: string;
}) {
    return (
        <Panel title="Konten Terbaru" className={cn('w-full', className)}>
            {items.length === 0 ? (
                <EmptyState
                    icon={FileText}
                    title="Belum ada konten"
                    description="Berita, agenda, dan dokumen terbaru akan muncul di sini."
                />
            ) : (
                <>
                    <div className="hidden grid-cols-[7rem_1fr_auto_12rem] gap-4 border-b border-border px-5 py-3 lg:grid">
                        <Text as="span" variant="fine-print" color="muted">
                            Jenis
                        </Text>
                        <Text as="span" variant="fine-print" color="muted">
                            Judul
                        </Text>
                        <Text as="span" variant="fine-print" color="muted">
                            Status
                        </Text>
                        <Text as="span" variant="fine-print" color="muted">
                            Terakhir diperbarui
                        </Text>
                    </div>

                    <ul className="divide-y divide-border">
                        {items.map((item) => (
                            <li key={`${item.type}-${item.id}`}>
                                <Link
                                    href={contentEditHref(item.type, item.id)}
                                    className="grid gap-2 px-5 py-4 transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none lg:grid-cols-[7rem_1fr_auto_12rem] lg:items-center lg:gap-4"
                                >
                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        color="muted"
                                    >
                                        {contentTypeLabel(item.type)}
                                    </Text>

                                    <Text
                                        as="span"
                                        variant="label"
                                        lineClamp={1}
                                    >
                                        {item.title}
                                    </Text>

                                    <span className="justify-self-start">
                                        <ContentStatusBadge
                                            type={item.type}
                                            status={item.status}
                                            label={item.status_label}
                                            isPublished={item.is_published}
                                        />
                                    </span>

                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        color="muted"
                                        className="tabular-nums"
                                    >
                                        {formatWibDateTime(
                                            item.updated_at,
                                            displayTimezone,
                                        )}
                                    </Text>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </>
            )}
        </Panel>
    );
}
