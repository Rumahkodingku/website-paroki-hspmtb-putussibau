export function formatPastorName(
    title: string | null | undefined,
    name: string,
): string {
    const normalizedTitle = title?.trim();
    const normalizedName = name.trim();

    if (!normalizedTitle) {
        return normalizedName;
    }

    if (
        normalizedName.toLowerCase().startsWith(normalizedTitle.toLowerCase())
    ) {
        return normalizedName;
    }

    return `${normalizedTitle} ${normalizedName}`;
}

export function formatServicePeriod(
    startYear: number | null,
    endYear: number | null,
    isCurrent: boolean,
): string {
    const start = startYear ?? 'Tahun tidak diketahui';

    if (isCurrent) {
        return `${start} – Sekarang`;
    }

    if (endYear !== null) {
        return `${start}–${endYear}`;
    }

    return `${start} – Akhir penugasan tidak diketahui`;
}
