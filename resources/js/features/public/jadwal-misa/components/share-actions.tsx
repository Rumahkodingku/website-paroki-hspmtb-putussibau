import { toast } from 'sonner';
import { Text } from '@/components/text';
import type { PublicLocation } from '../types';

/*
 * Share and print.
 *
 * XC-B1 fixes both mechanisms: WhatsApp is
 * https://wa.me/?text=<encoded title + URL>, and copying uses the Clipboard API
 * with a fallback. The share text is built from the page's own URL rather than a
 * stored one, so a filtered link is what gets shared — which is the point of
 * having the filter in the query string in the first place.
 */

type Props = {
    /** The shareable URL, read from window.location by the page. */
    url: string;
    title: string;
};

export function ShareActions({ url, title }: Props) {
    /**
     * Copy, with the fallback XC-B1 asks for.
     *
     * The fallback is not decorative: navigator.clipboard is undefined on an
     * insecure origin, and a parish site is very likely to be visited over plain
     * HTTP on a shared machine at some point. Silently doing nothing there would
     * leave the button looking broken with no explanation.
     */
    const copyLink = async () => {
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(url);
            } else {
                const field = document.createElement('textarea');
                field.value = url;
                field.setAttribute('readonly', '');
                field.style.position = 'absolute';
                field.style.left = '-9999px';
                document.body.appendChild(field);
                field.select();
                const copied = document.execCommand('copy');
                document.body.removeChild(field);

                if (!copied) {
                    throw new Error('execCommand refused');
                }
            }

            toast.success('Tautan halaman disalin.');
        } catch {
            toast.error('Tautan tidak dapat disalin. Salin dari bilah alamat.');
        }
    };

    const whatsappUrl = `https://wa.me/?text=${encodeURIComponent(
        `${title} ${url}`,
    )}`;

    return (
        <div
            className="flex flex-wrap items-center gap-3 print:hidden"
            data-test="share-actions"
        >
            <a
                href={whatsappUrl}
                target="_blank"
                rel="noopener noreferrer"
                data-test="share-whatsapp"
                className="inline-flex h-11 items-center rounded-md border border-border px-4 text-sm hover:border-primary-on-surface focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
            >
                Bagikan lewat WhatsApp
            </a>

            <button
                type="button"
                onClick={() => void copyLink()}
                data-test="share-copy"
                className="inline-flex h-11 items-center rounded-md border border-border px-4 text-sm hover:border-primary-on-surface focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
            >
                Salin tautan
            </button>

            <button
                type="button"
                onClick={() => window.print()}
                data-test="share-print"
                className="inline-flex h-11 items-center rounded-md border border-border px-4 text-sm hover:border-primary-on-surface focus-visible:border-primary focus-visible:ring-2 focus-visible:outline-none"
            >
                Cetak jadwal
            </button>
        </div>
    );
}

/**
 * A location's name, address and map link, shown under the schedule.
 *
 * PRD 7.3 asks for name, a short address and a "Buka di Google Maps" button.
 * The button is omitted entirely when there is nothing to point at, rather than
 * rendered disabled: a greyed-out Maps button on a parish's own page reads as a
 * bug rather than as an absence.
 */
export function LocationInfo({ location }: { location: PublicLocation }) {
    return (
        <div className="space-y-1">
            <Text as="p" variant="heading-sm">
                {location.name}
            </Text>

            {location.address ? (
                <Text as="p" variant="body-sm" color="muted">
                    {location.address}
                </Text>
            ) : null}

            {location.mapsUrl ? (
                <a
                    href={location.mapsUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    data-test="location-maps-link"
                    className="inline-flex items-center rounded-sm text-sm text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    Buka di Google Maps
                </a>
            ) : null}
        </div>
    );
}
