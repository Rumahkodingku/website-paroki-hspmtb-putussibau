import { CalendarDays, Wallet } from 'lucide-react';
import { Text } from '@/components/text';
import type { PublicServiceDetail } from '../types';

export function ScheduleAndFee({ service }: { service: PublicServiceDetail }) {
    if (!service.schedule_info && !service.fee_info) {
        return null;
    }

    return (
        <div className="grid gap-5 md:grid-cols-2 md:gap-6">
            {service.schedule_info ? (
                <div className="flex items-start gap-3 p-4 sm:p-5">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                        <CalendarDays className="size-5" aria-hidden="true" />
                    </div>
                    <div className="min-w-0">
                        <Text as="h3" variant="heading-sm">
                            Jadwal Pelaksanaan
                        </Text>
                        <Text
                            as="p"
                            variant="body-sm"
                            color="muted"
                            className="mt-2 leading-relaxed whitespace-pre-line"
                        >
                            {service.schedule_info}
                        </Text>
                    </div>
                </div>
            ) : null}

            {service.fee_info ? (
                <div className="flex items-start gap-3 p-4 sm:p-5">
                    <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-amber-500/10 text-amber-700 dark:text-amber-300">
                        <Wallet className="size-5" aria-hidden="true" />
                    </div>
                    <div className="min-w-0">
                        <Text as="h3" variant="heading-sm">
                            Biaya / Persembahan
                        </Text>
                        <Text
                            as="p"
                            variant="body-sm"
                            color="muted"
                            className="mt-2 leading-relaxed whitespace-pre-line"
                        >
                            {service.fee_info}
                        </Text>
                    </div>
                </div>
            ) : null}
        </div>
    );
}
