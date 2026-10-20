import { Link } from '@inertiajs/react';
import { CheckCircle2, Clock, ImageOff, type LucideIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Text } from '@/components/text';
import { cn } from '@/lib/utils';
import { contentEditHref, contentTypeLabel } from '../content-target';
import type { AttentionItem } from '../types';
import { Panel } from './panel';

/*
 * Perlu Perhatian: only the indicators that can be computed and acted on.
 *
 * Two qualify today. Expired documents come from a real date column, and
 * content without image alt text reads the article's own alt column — the value
 * the public page renders. Media upload failures and a missing Google Form are
 * deliberately absent: neither has a follow-up path in the admin today, and an
 * alarm with nowhere to go is worse than no alarm (D-77).
 *
 * An empty panel says so plainly rather than showing a zero.
 */

const COPY: Record<
    AttentionItem['key'],
    { icon: LucideIcon; title: (count: number) => string; description: string }
> = {
    expired_documents: {
        icon: Clock,
        title: (count) => `${count} dokumen kedaluwarsa`,
        description:
            'Sudah melewati tanggal berlakunya. Perbarui berkasnya atau ubah statusnya menjadi arsip.',
    },
    media_alt_text: {
        icon: ImageOff,
        title: (count) =>
            `${count} konten bergambar belum punya teks alternatif`,
        description:
            'Isi teks alternatif pada gambar agar dapat dibaca pembaca layar.',
    },
};

function AttentionBlock({ item }: { item: AttentionItem }) {
    const copy = COPY[item.key];
    const Icon = copy.icon;

    return (
        <div className="border-b border-border px-5 py-4 last:border-b-0">
            <div className="flex items-start gap-3">
                <span
                    className="flex size-9 shrink-0 items-center justify-center rounded-full bg-secondary text-foreground"
                    aria-hidden="true"
                >
                    <Icon className="size-4" />
                </span>

                <div className="min-w-0 space-y-1">
                    <Text as="h4" variant="label">
                        {copy.title(item.count)}
                    </Text>
                    <Text as="p" variant="fine-print" color="muted">
                        {copy.description}
                    </Text>
                </div>
            </div>

            {item.items.length > 0 ? (
                <ul className="mt-3 space-y-1.5 pl-12">
                    {item.items.map((entry) => (
                        <li key={`${entry.type}-${entry.id}`}>
                            <Link
                                href={contentEditHref(entry.type, entry.id)}
                                className="group inline-flex max-w-full items-baseline gap-2 text-label text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                <Text
                                    as="span"
                                    variant="fine-print"
                                    color="muted"
                                    className="shrink-0"
                                >
                                    {contentTypeLabel(entry.type)}
                                </Text>
                                <Text
                                    as="span"
                                    variant="label"
                                    lineClamp={1}
                                    className="group-hover:underline"
                                >
                                    {entry.title}
                                </Text>
                            </Link>
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
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
                <EmptyState
                    icon={CheckCircle2}
                    title="Tidak ada yang perlu ditindaklanjuti"
                    description="Semua konten dan berkas dalam kondisi baik."
                />
            ) : (
                <div>
                    {items.map((item) => (
                        <AttentionBlock key={item.key} item={item} />
                    ))}
                </div>
            )}
        </Panel>
    );
}
