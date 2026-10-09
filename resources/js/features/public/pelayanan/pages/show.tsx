import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { home } from '@/routes';
import { index as pelayanan } from '@/routes/pelayanan';

/*
 * /pelayanan/{slug} - Phase 03 placeholder detail page.
 *
 * Roadmap section 10 permits a placeholder public route for navigation testing
 * and forbids everything else: no query, no props, no CRUD, no fake content.
 *
 * Why the slug is printed into the page. This route answers 200 for any slug
 * matching [a-z0-9-]+, which is a deliberate and documented gap: PRD XC-E3
 * requires a draft or unpublished record to return 404, and nothing here knows
 * whether a record exists. Echoing the slug makes the gap visible during manual
 * review, instead of leaving a page that looks finished.
 *
 * The replacement is a route with model binding, at which point a missing slug
 * 404s on its own. See docs/DECISIONS.md D-31, which names the phase that
 * replaces this route, and PublicPlaceholderRoutesTest, which fails loudly if
 * the route ever disappears instead of being replaced.
 *
 * Each feature owns its own show page rather than sharing one placeholder
 * component, because ARCHITECTURE.md Part B section 4 forbids a feature from
 * reaching into another feature's folder. A shared page would have been deleted
 * out from under four unrelated routes the moment the first of them went live.
 */
export default function Pelayanan({ slug }: { slug: string }) {
    return (
        <PublicSection>
            <Breadcrumbs
                className="mb-8"
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Pelayanan', href: pelayanan() },
                    { title: `[ISI: judul Pelayanan]` },
                ]}
            />

            <SectionHeading
                eyebrow="Pelayanan"
                title={`[ISI: ${slug}]`}
                description="[ISI: detail Pelayanan untuk slug ini. Modul Pelayanan dibangun pada Fase 5.]"
            />
        </PublicSection>
    );
}
