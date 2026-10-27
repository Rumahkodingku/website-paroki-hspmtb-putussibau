import { Link } from '@inertiajs/react';
import { ArrowRight, CheckCircle2 } from 'lucide-react';
import { Text } from '@/components/text';
import { cn } from '@/lib/utils';
import { contentEditHref, contentTypeLabel } from '../content-target';
import type { AttentionItem } from '../types';
import { ATTENTION_COPY, type AttentionCopy } from '../attention-copy';
import { Panel } from './panel';

const COPY = ATTENTION_COPY as Record<AttentionItem['key'], AttentionCopy>;

function AttentionBlock({ item }: { item: AttentionItem }) {
    const copy = COPY[item.key];
    const Icon = copy.icon;

    return (
        <section className="px-4 py-4 sm:px-5">
            <div className="flex items-start gap-3">
                <span
                    className={cn(
                        'flex size-10 shrink-0 items-center justify-center rounded-xl',
                        'bg-amber-500/10 text-amber-700 dark:text-amber-400',
                    )}
                    aria-hidden="true"
                >
                    <Icon className="size-5" />
                </span>

                <div className="min-w-0 flex-1 space-y-1.5">
                    <div className="flex flex-wrap items-center gap-2">
                        <Text
                            as="h4"
                            variant="label"
                            className="font-semibold text-foreground"
                        >
                            {copy.title(item.count)}
                        </Text>

                        <span className="inline-flex items-center rounded-full bg-amber-500/10 px-2 py-0.5 text-xs font-medium text-amber-700 dark:text-amber-400">
                            Perlu diperiksa
                        </span>
                    </div>

                    <Text
                        as="p"
                        variant="fine-print"
                        color="muted"
                        className="leading-5"
                    >
                        {copy.description}
                    </Text>
                </div>
            </div>

            {item.items.length > 0 && (
                <ul className="mt-4 space-y-2 border-t border-border pt-3">
                    {item.items.map((entry) => (
                        <li key={`${entry.type}-${entry.id}`}>
                            <Link
                                href={contentEditHref(entry.type, entry.id)}
                                className={cn(
                                    'group flex min-w-0 items-center gap-3 rounded-lg',
                                    'px-2 py-2 transition-colors hover:bg-muted/60',
                                    'focus-visible:ring-2 focus-visible:outline-none',
                                    'focus-visible:ring-ring',
                                )}
                            >
                                <span className="min-w-0 flex-1 space-y-0.5">
                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        color="muted"
                                        className="block"
                                    >
                                        {contentTypeLabel(entry.type)}
                                    </Text>

                                    <Text
                                        as="span"
                                        variant="label"
                                        lineClamp={2}
                                        className="block font-medium group-hover:text-primary"
                                    >
                                        {entry.title}
                                    </Text>
                                </span>

                                <ArrowRight
                                    className="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary"
                                    aria-hidden="true"
                                />
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

export function AttentionPanel({
    items,
    className,
}: {
    items: AttentionItem[];
    className?: string;
}) {
    return (
        <Panel title="Perlu Perhatian" className={cn('h-full', className)}>
            {items.length === 0 ? (
                <div className="flex min-h-48 flex-col items-center justify-center px-5 py-8 text-center sm:min-h-56">
                    <span
                        className="mb-3 flex size-11 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-700 dark:text-emerald-400"
                        aria-hidden="true"
                    >
                        <CheckCircle2 className="size-6" />
                    </span>

                    <Text
                        as="h4"
                        variant="label"
                        className="font-semibold text-foreground"
                    >
                        Tidak ada yang perlu ditindaklanjuti
                    </Text>

                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="mt-1 max-w-xs leading-relaxed"
                    >
                        Tidak ada masalah yang dilaporkan oleh pemeriksaan
                        dashboard saat ini.
                    </Text>
                </div>
            ) : (
                <div className="divide-y divide-border">
                    {items.map((item) => (
                        <AttentionBlock key={item.key} item={item} />
                    ))}
                </div>
            )}
        </Panel>
    );
}
