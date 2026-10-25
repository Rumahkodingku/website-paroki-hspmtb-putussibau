import {
    CalendarClock,
    CalendarDays,
    FileText,
    HandHeart,
    Newspaper,
    type LucideIcon,
} from 'lucide-react';
import type { DashboardSummary } from '../types';

export type Metric = {
    label: string;
    value: number;
    tone?: 'neutral' | 'success' | 'warning' | 'danger';
};

export type SummaryCardConfig = {
    label: string;
    value: number;
    icon: LucideIcon;
    iconClassName: string;
    iconBackgroundClassName: string;
    breakdown: Metric[];
};

export function buildSummaryCards(
    summary: DashboardSummary,
): SummaryCardConfig[] {
    const { posts, events, documents, massSchedules, services } = summary;

    return [
        {
            label: 'Total Berita',
            value: posts.total,
            icon: Newspaper,
            iconClassName: 'text-primary',
            iconBackgroundClassName: 'bg-primary/10',
            breakdown: [
                {
                    label: 'Draf',
                    value: posts.draft,
                    tone: posts.draft > 0 ? 'warning' : 'neutral',
                },
                {
                    label: 'Terjadwal',
                    value: posts.scheduled,
                    tone: posts.scheduled > 0 ? 'warning' : 'neutral',
                },
                { label: 'Terbit', value: posts.published, tone: 'success' },
                { label: 'Arsip', value: posts.archived, tone: 'neutral' },
            ],
        },
        {
            label: 'Agenda Mendatang',
            value: events.upcoming,
            icon: CalendarClock,
            iconClassName: 'text-primary',
            iconBackgroundClassName: 'bg-primary/10',
            breakdown: [
                {
                    label: 'Terjadwal',
                    value: events.upcoming,
                    tone: 'warning',
                },
                {
                    label: 'Draf',
                    value: events.draft,
                    tone: events.draft > 0 ? 'warning' : 'neutral',
                },
                {
                    label: 'Ditunda',
                    value: events.postponed,
                    tone: events.postponed > 0 ? 'warning' : 'neutral',
                },
                {
                    label: 'Dibatalkan',
                    value: events.cancelled,
                    tone: events.cancelled > 0 ? 'danger' : 'neutral',
                },
            ],
        },
        {
            label: 'Dokumen',
            value: documents.total,
            icon: FileText,
            iconClassName: 'text-slate-700 dark:text-slate-200',
            iconBackgroundClassName: 'bg-slate-500/10',
            breakdown: [
                {
                    label: 'Draf',
                    value: documents.draft,
                    tone: documents.draft > 0 ? 'warning' : 'neutral',
                },
                {
                    label: 'Terbit',
                    value: documents.published,
                    tone: 'success',
                },
                { label: 'Arsip', value: documents.archived, tone: 'neutral' },
                {
                    label: 'Kedaluwarsa',
                    value: documents.expired,
                    tone: documents.expired > 0 ? 'danger' : 'neutral',
                },
            ],
        },
        {
            label: 'Jadwal Misa',
            value: massSchedules.regular,
            icon: CalendarDays,
            iconClassName: 'text-primary',
            iconBackgroundClassName: 'bg-primary/10',
            breakdown: [
                {
                    label: 'Rutin aktif',
                    value: massSchedules.regular,
                    tone: 'success',
                },
                {
                    label: 'Misa khusus',
                    value: massSchedules.special,
                    tone: massSchedules.special > 0 ? 'warning' : 'neutral',
                },
                {
                    label: 'Pemberitahuan',
                    value: massSchedules.notices,
                    tone: massSchedules.notices > 0 ? 'danger' : 'neutral',
                },
            ],
        },
        {
            label: 'Pelayanan',
            value: services.active,
            icon: HandHeart,
            iconClassName: 'text-amber-600',
            iconBackgroundClassName: 'bg-amber-500/10',
            breakdown: [
                { label: 'Aktif', value: services.active, tone: 'success' },
                {
                    label: 'Nonaktif',
                    value: services.inactive,
                    tone: services.inactive > 0 ? 'warning' : 'neutral',
                },
            ],
        },
    ];
}
