/**
 * Serialize JSON-LD so no value can close the script element.
 *
 * A title of `</script><img onerror=...>` is valid plain text and reaches this
 * function unchanged. The `<` escape keeps it inside the script as data.
 */
export function serializeJsonLd(data: Record<string, unknown>): string {
    return JSON.stringify(data).replace(/</g, '\\u003c');
}

export function absoluteJsonLdUrl(
    value: string | null | undefined,
    appUrl: string,
): string | null {
    if (value === null || value === undefined || value.trim() === '') {
        return null;
    }

    if (/^https?:\/\//i.test(value) || value.startsWith('//')) {
        return value;
    }

    return `${appUrl}${value.startsWith('/') ? value : `/${value}`}`;
}
