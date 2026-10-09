import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /komunitas - Phase 03 placeholder.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no business rules, no fake
 * content. The real Komunitas page arrives in Fase 5, along with the model, the
 * resource and the scoping rules PRD 7.7 specifies.
 */
export default function Komunitas() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Komunitas"
                title="[ISI: Komunitas]"
                description="[ISI: daftar OMK, WKRI, kategorial, wilayah, dan lingkungan. Modul Komunitas dibangun pada Fase 5.]"
            />
        </PublicSection>
    );
}
