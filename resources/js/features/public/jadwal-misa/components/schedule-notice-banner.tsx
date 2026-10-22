import { AlertCircle } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import type { PublicScheduleNotice } from '../types';

type Props = {
    notices: PublicScheduleNotice[];
};

export function ScheduleNoticeBanner({ notices }: Props) {
    if (notices.length === 0) {
        return null;
    }

    return (
        <section
            aria-label="Pemberitahuan perubahan jadwal"
            data-test="schedule-notice-banner"
            className="space-y-4"
        >
            <ul className="space-y-3">
                {notices.map((notice) => (
                    <li key={notice.id}>
                        <Alert
                            variant="destructive"
                            className="rounded-lg border-primary/20 p-4 sm:p-5"
                        >
                            <AlertCircle
                                className="mt-0.5 size-5"
                                aria-hidden="true"
                            />

                            <AlertTitle className="line-clamp-none text-sm leading-5 font-semibold sm:text-base">
                                {notice.title}
                            </AlertTitle>

                            <AlertDescription className="mt-1 text-sm leading-relaxed">
                                {notice.message}
                            </AlertDescription>
                        </Alert>
                    </li>
                ))}
            </ul>
        </section>
    );
}
