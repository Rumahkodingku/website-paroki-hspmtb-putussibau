import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { DocumentCard } from '@/components/document-card';
import type { PublicServiceDetail } from '../../types';

export function DocumentsSection({
    service,
}: {
    service: PublicServiceDetail;
}) {
    if (service.documents.length === 0) return null;

    return (
        <PublicSection surface="soft" className="py-10 sm:py-12 lg:py-16">
            <SectionHeading
                title="Dokumen Terkait"
                description="Unduh dokumen pendukung yang tersedia untuk pelayanan ini."
            />
            <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                {service.documents.map((document) => (
                    <DocumentCard key={document.id} document={document} />
                ))}
            </div>
        </PublicSection>
    );
}
