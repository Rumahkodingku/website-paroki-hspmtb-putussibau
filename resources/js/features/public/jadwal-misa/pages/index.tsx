import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';

/*
 * /jadwal-misa - Phase 03 placeholder.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no business rules, no fake
 * content. The schedule itself arrives with the Mass Schedule phase, along with
 * NextMassResolver, which is explicitly out of scope here (section 20).
 *
 * The [ISI: ] prefix is deliberate and required. AGENTS.md and DESIGN.md both
 * forbid inventing a parish schedule, and a plausible-looking mass timetable is
 * the single easiest thing on this page to invent by accident.
 */
export default function JadwalMisa() {
    return (
        <PublicSection>
            <SectionHeading
                eyebrow="Ibadah"
                title="[ISI: Jadwal Misa]"
                description="[ISI: jadwal misa rutin, misa khusus, dan pemberitahuan perubahan jadwal. Modul Jadwal Misa dibangun pada Fase 4.]"
            />
        </PublicSection>
    );
}
