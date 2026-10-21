import type { PublicBoardMember, PublicBoardPeriod } from '../types';

export function sortMembers(members: PublicBoardMember[]): PublicBoardMember[] {
    return [...members].sort((a, b) => a.sort_order - b.sort_order);
}

export function formatPeriod(period: PublicBoardPeriod): string {
    const start = period.start_year ?? '?';

    if (period.is_active && period.end_year === null) {
        return `${start}–sekarang`;
    }

    return `${start}–${period.end_year ?? 'sekarang'}`;
}
