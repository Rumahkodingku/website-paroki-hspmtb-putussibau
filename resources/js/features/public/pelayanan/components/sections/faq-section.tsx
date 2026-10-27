import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { FaqList } from '../faq-list';
import type { PublicServiceDetail } from '../../types';

export function FaqSection({ service }: { service: PublicServiceDetail }) {
    if (service.faqs.length === 0) return null;

    return (
        <PublicSection className="py-10 sm:py-12 lg:py-16">
            <div className="mx-auto max-w-3xl">
                <SectionHeading
                    title="Pertanyaan yang Sering Diajukan"
                    className="mb-6"
                />
                <FaqList items={service.faqs} />
            </div>
        </PublicSection>
    );
}
