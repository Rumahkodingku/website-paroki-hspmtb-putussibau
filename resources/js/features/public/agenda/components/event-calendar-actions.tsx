import { Button } from '@/components/ui/button';
import { Text } from '@/components/text';
import { CalendarPlus, Download, Share2 } from 'lucide-react';
import { googleCalendarUrl } from '@/lib/datetime';
import { ics as eventIcs } from '@/routes/agenda';
import type { PublicEventDetail } from '../types';

export function EventCalendarActions({
    event,
    displayTimezone,
    url,
}: {
    event: PublicEventDetail;
    displayTimezone: string;
    url: string;
}) {
    const canAddToCalendar = event.status !== 'cancelled';

    const handleShare = async () => {
        if (typeof navigator === 'undefined' || !url) return;

        try {
            if (navigator.share) {
                await navigator.share({ title: event.title, url });
                return;
            }

            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(url);
            }
        } catch {
            // Share cancellation is expected; keep the page usable without a toast.
        }
    };

    return (
        <div className="mt-6 space-y-3 border-t border-border pt-5">
            {canAddToCalendar ? (
                <>
                    <Button asChild className="w-full py-7 text-sm">
                        <a
                            href={googleCalendarUrl({
                                title: event.title,
                                start: event.start_at,
                                end: event.end_at,
                                details: event.plain_description,
                                location: event.location_name,
                                isAllDay: event.is_all_day,
                                timeZone: displayTimezone,
                            })}
                            target="_blank"
                            rel="noopener noreferrer"
                            data-test="add-to-google"
                        >
                            <CalendarPlus aria-hidden="true" />
                            Tambahkan ke Google Calendar
                        </a>
                    </Button>
                    <Button
                        asChild
                        variant="secondary"
                        className="w-full py-7 text-sm"
                    >
                        <a
                            href={eventIcs(event.slug).url}
                            data-test="download-ics"
                        >
                            <Download aria-hidden="true" />
                            Unduh kalender (.ics)
                        </a>
                    </Button>
                </>
            ) : (
                <Text as="p" variant="body-sm" color="muted">
                    Kalender tidak tersedia untuk kegiatan yang dibatalkan.
                </Text>
            )}
            <Button
                type="button"
                variant="outline"
                className="w-full py-7 text-sm"
                onClick={handleShare}
            >
                <Share2 aria-hidden="true" />
                Bagikan kegiatan
            </Button>
        </div>
    );
}
