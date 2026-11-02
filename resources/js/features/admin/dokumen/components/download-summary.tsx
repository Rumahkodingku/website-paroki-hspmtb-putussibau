import { Link } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { Text } from '@/components/text';
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/lib/utils';
import { edit as editDocument } from '@/routes/documents';
import type { DocumentDownloadSummary } from '../types';

export function DownloadSummary({
    summary,
}: {
    summary: DocumentDownloadSummary;
}) {
    if (summary.total === 0) {
        return null;
    }

    return (
        <Card className="gap-0 rounded-xl border-border bg-card py-0 shadow-none">
            <CardContent className="flex flex-col gap-3 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                <div className="flex items-center gap-3">
                    <span
                        className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        aria-hidden="true"
                    >
                        <Download className="size-5" />
                    </span>

                    <div className="min-w-0">
                        <Text
                            as="p"
                            variant="fine-print"
                            color="muted"
                            className="leading-5"
                        >
                            Total unduhan dokumen terbit
                        </Text>

                        <Text
                            as="p"
                            variant="heading-md"
                            className="tabular-nums"
                        >
                            {summary.total.toLocaleString('id-ID')}
                        </Text>
                    </div>
                </div>

                {summary.top.length > 0 && (
                    <ol className="flex min-w-0 flex-wrap gap-2 lg:justify-end">
                        {summary.top.map((item, index) => (
                            <li key={item.id} className="min-w-0">
                                <Link
                                    href={editDocument(item.id).url}
                                    className={cn(
                                        'group flex min-w-0 items-center gap-2 rounded-md border border-border/70 px-2.5 py-1.5',
                                        'transition-colors hover:bg-muted/60',
                                        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                                    )}
                                >
                                    <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-muted text-[11px] font-semibold text-muted-foreground tabular-nums">
                                        {index + 1}
                                    </span>

                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        lineClamp={1}
                                        className="max-w-[14rem] font-medium text-foreground group-hover:text-primary"
                                    >
                                        {item.title}
                                    </Text>

                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        color="muted"
                                        className="shrink-0 tabular-nums"
                                    >
                                        {item.download_count.toLocaleString(
                                            'id-ID',
                                        )}{' '}
                                        unduhan
                                    </Text>
                                </Link>
                            </li>
                        ))}
                    </ol>
                )}
            </CardContent>
        </Card>
    );
}
