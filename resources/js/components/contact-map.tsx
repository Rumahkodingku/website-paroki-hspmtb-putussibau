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
        </div>
    );
}

export default ContactMap;
