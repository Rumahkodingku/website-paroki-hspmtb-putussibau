import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /profil - the entry point to the parish profile.
 *
 * Phase 03 placeholder. Roadmap section 10 permits a placeholder public route
 * for navigation testing and forbids everything else: no query, no props, no
 * business rules, no fake content.
 *
 * PRD 5.1 allows this page to either redirect or show a summary. It shows a
 * summary rather than redirecting, because a redirect would make /profil a
 * dead end in the middle of the navigation and the sub-pages need somewhere to
 * point back to.
 */
export default function Profil() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Profil"
                title="[ISI: judul profil paroki]"
                description="[ISI: ringkasan singkat siapa paroki ini. Satu atau dua kalimat yang membuat pengunjung baru memahami sebelum menjelajah.]"
            />
        </PublicSection>
    );
}
