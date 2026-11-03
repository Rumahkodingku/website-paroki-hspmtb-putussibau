import { Head } from '@inertiajs/react';
import { serializeJsonLd } from '@/lib/json-ld';

/**
 * One place that writes a JSON-LD script into the Inertia head.
 *
 * Keeping the `dangerouslySetInnerHTML` here means every caller gets the same
 * serializer, which escapes `<` before the value can close the script element.
 */
export function JsonLd({
    data,
    headKey,
}: {
    data: Record<string, unknown>;
    headKey: string;
}) {
    return (
        <Head>
            <script
                type="application/ld+json"
                head-key={headKey}
                dangerouslySetInnerHTML={{
                    __html: serializeJsonLd(data),
                }}
            />
        </Head>
    );
}
