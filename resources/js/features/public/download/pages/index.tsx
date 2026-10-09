import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /download - Phase 03 placeholder.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no business rules, no fake
 * content. The real Download page arrives in Fase 6, along with the model, the
 * resource and the scoping rules PRD 7.10 specifies.
 */
export default function Download() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Download"
                title="[ISI: Download]"
                description="[ISI: daftar dokumen resmi paroki per kategori. Modul Download dibangun pada Fase 6.]"
            />
        </PublicSection>
    );
}
