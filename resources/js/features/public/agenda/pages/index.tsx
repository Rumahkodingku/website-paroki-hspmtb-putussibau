import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /agenda - Phase 03 placeholder.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no business rules, no fake
 * content. The real Agenda page arrives in Fase 4, along with the model, the
 * resource and the scoping rules PRD 7.5 specifies.
 */
export default function Agenda() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Agenda"
                title="[ISI: Agenda]"
                description="[ISI: kalender, daftar kegiatan, dan detail kegiatan. Modul Agenda dibangun pada Fase 4.]"
            />
        </PublicSection>
    );
}
