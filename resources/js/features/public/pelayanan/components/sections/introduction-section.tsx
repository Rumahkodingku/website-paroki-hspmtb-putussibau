import { PublicSection } from '@/components/public-section';
import { RichText } from '@/components/rich-text';
import { SectionHeading } from '@/components/section-heading';
import type { PublicServiceDetail } from '../../types';

export function IntroductionSection({
    service,
}: {
    service: PublicServiceDetail;
}) {
    if (!service.introduction) return null;

    return (
        <PublicSection surface="soft" className="py-10 sm:py-12 lg:py-16">
            <div className="mx-auto max-w-3xl">
                <p className="text-xs font-semibold tracking-[0.16em] text-primary uppercase">
                    Tentang Sakramen
                </p>
                <SectionHeading
                    title={`Makna ${service.title}`}
                    level={2}
                    className="mt-2"
                />
                <RichText
                    html={service.introduction}
                    className="prose-p:leading-7 sm:prose-p:leading-8 mt-4 max-w-none"
                />
            </div>
        </PublicSection>
    );
}
