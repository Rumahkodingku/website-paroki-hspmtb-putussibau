import { Text } from '@/components/text';

/*
 * The banner announcing a change to the schedule.
 *
 * Deliberately loud. PRD 7.3 asks for "kotak peringatan jika ada notice aktif",
 * and a notice exists because a mass moved — someone who misses it arrives at the
 * church at the wrong hour. It is the one place on this page where the design
 * system's restraint is deliberately set aside, and it is set aside in the one
 * colour already reserved for "act now" in every other context.
 *
 * All of them, not the first: two notices can be in their window at once (a
 * general one and one for a specific stasi), and showing only the first would hide
 * half the information.
 */

type Props = {
    notices: Array<{ id: number; title: string; message: string }>;
};

export function ScheduleNoticeBanner({ notices }: Props) {
    if (notices.length === 0) {
        return null;
    }

    return (
        <section
            aria-label="Pemberitahuan perubahan jadwal"
            data-test="schedule-notice-banner"
            className="space-y-4 rounded-lg border border-primary/40 bg-primary/5 p-6"
        >
            <Text as="h2" variant="h3" className="text-primary-on-surface">
                Pemberitahuan Perubahan Jadwal
            </Text>

            <ul className="space-y-4">
                {notices.map((notice) => (
                    <li key={notice.id} className="space-y-1">
                        <Text as="p" variant="heading-sm">
                            {notice.title}
                        </Text>
                        <Text as="p" variant="body">
                            {notice.message}
                        </Text>
                    </li>
                ))}
            </ul>
        </section>
    );
}
