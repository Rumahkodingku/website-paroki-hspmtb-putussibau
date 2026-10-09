import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /kontak - Phase 03 placeholder.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no business rules, no fake
 * content. The real Kontak page arrives in Fase 6, along with the model, the
 * resource and the scoping rules PRD 7.9 specifies.
 */
export default function Kontak() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Kontak"
                title="[ISI: Kontak]"
                description="[ISI: alamat, telepon, surel, jam layanan sekretariat, peta, dan media sosial paroki. Modul Kontak dibangun pada Fase 6.]"
            />
        </PublicSection>
    );
}
