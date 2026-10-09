import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /galeri - Phase 03 placeholder.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no business rules, no fake
 * content. The real Galeri page arrives in Fase 6, along with the model, the
 * resource and the scoping rules PRD 7.8 specifies.
 */
export default function Galeri() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Galeri"
                title="[ISI: Galeri]"
                description="[ISI: daftar album beserta foto-fotonya. Modul Galeri dibangun pada Fase 6.]"
            />
        </PublicSection>
    );
}
