import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /berita - Phase 03 placeholder.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no business rules, no fake
 * content. The real Berita page arrives in Fase 4, along with the model, the
 * resource and the scoping rules PRD 7.4 specifies.
 */
export default function Berita() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Berita & Artikel"
                title="[ISI: Berita]"
                description="[ISI: daftar artikel terbit dengan filter dan pencarian. Modul Berita dibangun pada Fase 4.]"
            />
        </PublicSection>
    );
}
