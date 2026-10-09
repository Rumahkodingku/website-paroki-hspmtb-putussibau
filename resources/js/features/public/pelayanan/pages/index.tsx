import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /pelayanan - Phase 03 placeholder.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no business rules, no fake
 * content. The real Pelayanan page arrives in Fase 5, along with the model, the
 * resource and the scoping rules PRD 7.6 specifies.
 */
export default function Pelayanan() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Pelayanan"
                title="[ISI: Pelayanan]"
                description="[ISI: indeks layanan sakramen beserta syarat, langkah, dan FAQ. Modul Pelayanan dibangun pada Fase 5.]"
            />
        </PublicSection>
    );
}
