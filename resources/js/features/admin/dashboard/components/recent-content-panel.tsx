import { Text } from '@/components/text';
import { EmptyState } from '@/components/empty-state';
import { FileText } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { RecentContentItem } from '../types';
import { Panel } from './panel';
import { RecentContentRow } from './recent-content-row';

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
        <Panel
            title="Konten Terakhir Diperbarui"
            className={cn('w-full', className)}
        >
            {items.length === 0 ? (
                <EmptyState
                    icon={FileText}
                    title="Belum ada konten"
                    description="Berita, agenda, dan dokumen terbaru akan muncul di sini."
                />
            ) : (
                <>
                    <div
                        className={cn(
                            'hidden border-b border-border bg-muted/30',
                            'px-5 py-3 lg:grid',
                            'lg:grid-cols-[8rem_minmax(0,1fr)_auto_12rem]',
                            'lg:items-center lg:gap-4',
                        )}
                    >
                        {[
                            'Jenis',
                            'Judul',
                            'Status',
                            'Terakhir diperbarui',
                        ].map((label) => (
                            <Text
                                key={label}
                                as="span"
                                variant="fine-print"
                                color="muted"
                                className="font-medium"
                            >
                                {label}
                            </Text>
                        ))}
                    </div>

                    <ul className="divide-y divide-border">
                        {items.slice(0, 8).map((item) => (
                            <RecentContentRow
                                key={`${item.type}-${item.id}`}
                                item={item}
                                displayTimezone={displayTimezone}
                            />
                        ))}
                    </ul>
                </>
            )}
        </Panel>
    );
}
