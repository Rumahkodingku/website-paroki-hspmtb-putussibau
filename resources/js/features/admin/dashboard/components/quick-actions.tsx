import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import {
    CalendarDays,
    CalendarPlus,
    FileText,
    Settings,
    type LucideIcon,
} from 'lucide-react';
import { Text } from '@/components/text';
import { create as createEvent } from '@/routes/events';
import { list as massSchedulesList } from '@/routes/mass-schedules';
import { create as createPost } from '@/routes/posts';
import { edit as editSettings } from '@/routes/settings';
import { cn } from '@/lib/utils';
import { Panel } from './panel';

/*
 * The four things an admin opens the dashboard to do.
 *
 * Each is a real Inertia visit through a Wayfinder route, so a moved admin URL
 * moves the shortcut with it. Nothing here is decorative.
 */

function QuickAction({
    href,
    title,
    description,
    icon: Icon,
}: {
    href: string;
    title: string;
    description: string;
    icon: LucideIcon;
}) {
    return (
        <Link
            href={href}
            className="group flex items-start gap-3 rounded-lg border border-border p-3 transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            <span
                className="flex size-9 shrink-0 items-center justify-center rounded-full bg-secondary text-foreground"
                aria-hidden="true"
            >
                <Icon className="size-4" />
            </span>
            <span className="min-w-0 space-y-0.5">
                <Text as="span" variant="label" className="block">
                    {title}
                </Text>
                <Text
                    as="span"
                    variant="fine-print"
                    color="muted"
                    className="block"
                >
                    {description}
                </Text>
            </span>
        </Link>
    );
}

export function QuickActions({ className }: { className?: string }): ReactNode {
    return (
        <Panel title="Aksi Cepat" className={cn('h-full', className)}>
            <div className="grid gap-2 p-4 sm:grid-cols-2 lg:grid-cols-1 xl:grid-cols-2">
                <QuickAction
                    href={createPost().url}
                    title="Tulis Berita"
                    description="Artikel, renungan, pengumuman"
                    icon={FileText}
                />
                <QuickAction
                    href={createEvent().url}
                    title="Tambah Agenda"
                    description="Kegiatan dan perayaan"
                    icon={CalendarPlus}
                />
                <QuickAction
                    href={massSchedulesList().url}
                    title="Kelola Jadwal Misa"
                    description="Misa rutin dan khusus"
                    icon={CalendarDays}
                />
                <QuickAction
                    href={editSettings().url}
                    title="Pengaturan Situs"
                    description="Identitas, kontak, beranda"
                    icon={Settings}
                />
            </div>
        </Panel>
    );
}
