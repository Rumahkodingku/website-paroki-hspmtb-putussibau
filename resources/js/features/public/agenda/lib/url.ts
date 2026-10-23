import { index as agendaIndex } from '@/routes/agenda';
import type { AgendaMode } from '../types';

/**
 * Build a shareable agenda URL while omitting default and empty parameters.
 */
export function agendaUrl(params: {
    mode: AgendaMode;
    kategori: string;
    bulan: string;
}): string {
    const query = new URLSearchParams();

    if (params.mode !== 'daftar') {
        query.set('mode', params.mode);
    }

    if (params.kategori !== '') {
        query.set('kategori', params.kategori);
    }

    if (params.bulan !== '') {
        query.set('bulan', params.bulan);
    }

    const search = query.toString();

    return search === '' ? agendaIndex().url : `${agendaIndex().url}?${search}`;
}

/**
 * Format a YYYY-MM value as an Indonesian month and year.
 * UTC prevents the first day of a month from shifting across timezones.
 */
export function formatMonthLabel(month: string): string {
    return new Intl.DateTimeFormat('id-ID', {
        month: 'long',
        year: 'numeric',
        timeZone: 'UTC',
    }).format(new Date(`${month}-01T12:00:00Z`));
}
