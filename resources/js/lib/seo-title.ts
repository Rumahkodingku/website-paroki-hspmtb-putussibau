/**
 * The client-side twin of App\Support\SeoTitle.
 *
 * The server renders the title into the first response and React renders it
 * again on navigation. Both use the same rule so a page never changes its
 * title when JavaScript takes over.
 */
export function formatSeoTitle(
    title: string,
    siteName: string | null | undefined,
): string {
    const trimmed = title.trim();
    const site = siteName?.trim();

    if (!trimmed) {
        return site ?? '';
    }

    if (!site || trimmed === site || trimmed.includes(site)) {
        return trimmed;
    }

    return `${trimmed} | ${site}`;
}
