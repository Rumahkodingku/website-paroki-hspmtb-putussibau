import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { ScheduleAndFee } from '../schedule-and-fee';
import type { PublicServiceDetail } from '../../types';

export function ScheduleFeeDesktopSection({
    service,
}: {
    service: PublicServiceDetail;
}) {
    if (!service.schedule_info && !service.fee_info) return null;

    return (
        <PublicSection
            surface="soft"
            className="hidden py-10 sm:py-12 lg:block lg:py-16"
        >
            <div className="mx-auto max-w-5xl">
                <SectionHeading
                    title="Jadwal dan Informasi Biaya"
                    description="Periksa informasi pelaksanaan dan ketentuan biaya sebelum menghubungi sekretariat."
                />
                <div className="mt-6">
                    <ScheduleAndFee service={service} />
                </div>
            </div>
        </PublicSection>
    );
}
