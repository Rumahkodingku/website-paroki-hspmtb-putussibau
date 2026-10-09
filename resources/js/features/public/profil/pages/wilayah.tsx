import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /profil/wilayah - one static Profile sub-page.
 *
 * Phase 03 placeholder. PRD 5.1 gives each of these its own URL so a bookmarked
 * link keeps working, which is why they are separate pages rather than one page
 * behind a query parameter.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no business rules, no fake
 * content.
 *
 * The sub-navigation tying these five pages together - DESIGN.md
 * components.ProfileSubNav - arrives with the Profile phase, when there is
 * content to navigate. The breadcrumb is the only hierarchy here for now.
 */
export default function Wilayah() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Profil"
                title="[ISI: Wilayah Pelayanan]"
                description="[ISI: daftar wilayah dan lingkungan yang dilayani, beserta batasannya.]"
            />
        </PublicSection>
    );
}
