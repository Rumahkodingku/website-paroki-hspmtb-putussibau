import { MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';

/**
 * The map and the directions action, each only when its URL is valid.
 *
 * `maps_link` is never used as an iframe source (KON-07): it is a directions
 * destination, and the embed URL has already been through the Google Maps host
 * allowlist on the server. The frame is lazy and given a title so it is not an
 * unlabelled region to a screen reader.
 *
 * Lifted to components/ in Phase 9A: the contact page and the homepage contact
 * block both render it, and a feature may not import another feature.
 */
export function ContactMap({
    embedUrl,
    directionsUrl,
}: {
    embedUrl: string | null;
    directionsUrl: string | null;
}) {
    if (!embedUrl && !directionsUrl) {
        return null;
    }

    return (
        <div className="space-y-4">
            {embedUrl ? (
                <div className="aspect-video w-full overflow-hidden rounded-lg border border-border bg-muted">
                    <iframe
                        src={embedUrl}
                        title="Peta lokasi paroki"
                        className="h-full w-full"
                        loading="lazy"
                        referrerPolicy="no-referrer-when-downgrade"
                        allowFullScreen
                    />
                </div>
            ) : null}

            {directionsUrl ? (
                <Button asChild>
                    <a
                        href={directionsUrl}
                        target="_blank"
                        rel="noopener noreferrer"
                    >
                        <MapPin aria-hidden="true" />
                        Petunjuk Arah
                    </a>
                </Button>
            ) : null}
        </div>
    );
}

export default ContactMap;
