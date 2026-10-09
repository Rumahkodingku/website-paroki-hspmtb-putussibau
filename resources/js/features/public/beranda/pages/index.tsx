import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { ParishCard, ParishCardContent } from '@/components/parish-card';
import { PublicSection } from '@/components/public-section';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';
import { index as jadwalMisa } from '@/routes/jadwal-misa';

/*
 * Guest entry point.
 *
 * This is still the Phase 01 foundation page, not the finished homepage. PRD 13
 * Fase 4 owns the real Beranda: hero slider, announcement bar, next-mass
 * resolver, latest news, upcoming agenda and the rest, all of which need tables
 * that do not exist yet.
 *
 * Phase 03 changed the markup, not the content. The hand-written container,
 * section and card classes are replaced by the shared public components, so this
 * page is now a real exercise of the foundation rather than a fourth place that
 * spells out max-w-7xl and px-4 on its own. Every [ISI: ...] placeholder and the
 * "Selamat Datang" title stay exactly as they were: tests/e2e/public.spec.ts
 * asserts on that title, and DESIGN.md forbids inventing parish content.
 */
export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Selamat Datang" />

            <PublicSection>
                <div className="flex flex-col gap-8">
                    <section className="space-y-6">
                        <Text as="p" variant="caption-strong" color="danger">
                            Paroki Hati Santa Perawan Maria Tak Bernoda
                        </Text>

                        {/*
                            variant h1 rather than display-md: this is the
                            page's h1 and the largest heading the public site
                            uses. display-md is the section step, and using it
                            for both is what made every h1, h2 and h3 the same
                            size. text-balance comes with the variant - a
                            one-sentence welcome that breaks badly reads as a
                            bug.
                        */}
                        <Text as="h1" variant="h1" className="max-w-3xl">
                            [ISI: kalimat sambutan paroki]
                        </Text>

                        <Text
                            as="p"
                            variant="body"
                            color="muted"
                            className="max-w-2xl"
                        >
                            [ISI: ringkasan singkat siapa kami dan mengapa situs
                            ini ada. Dipilih agar pengunjung baru memahami
                            sebelum menjelajah.]
                        </Text>

                        <div className="flex flex-wrap items-center gap-3">
                            {auth.user ? (
                                <Button asChild size="lg">
                                    <Link href={dashboard()}>
                                        Dashboard
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            ) : (
                                /*
                                    No Register link: PRD AUTH-R2 forbids public
                                    registration, so no such route exists.
                                    See docs/DECISIONS.md D-19.
                                */
                                <Button asChild size="lg">
                                    <Link href={login()}>Masuk</Link>
                                </Button>
                            )}

                            {/*
                                Used to be an empty-fragment href in Phase 01,
                                which is navigation to nowhere: the browser
                                resolves it to the current page, so the visitor
                                stays put and has no reason to think the click
                                failed. /jadwal-misa exists as of Phase 03, so
                                this points at it. The label stays bracketed
                                because the schedule itself still has to come
                                from the parish.
                            */}
                            <Button asChild size="lg" variant="outline">
                                <Link href={jadwalMisa()}>
                                    [ISI: tautan jadwal misa]
                                </Link>
                            </Button>
                        </div>
                    </section>

                    <section className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            { label: 'Jadwal Misa', value: '[ISI: jadwal]' },
                            { label: 'Berita', value: '[ISI: berita terbaru]' },
                            { label: 'Agenda', value: '[ISI: agenda]' },
                            {
                                label: 'Pelayanan',
                                value: '[ISI: layanan pastorat]',
                            },
                        ].map((item) => (
                            <ParishCard key={item.label}>
                                <ParishCardContent className="flex flex-col gap-2">
                                    <Text
                                        as="p"
                                        variant="caption-strong"
                                        color="muted"
                                    >
                                        {item.label}
                                    </Text>
                                    <Text
                                        as="p"
                                        variant="body"
                                        color="foreground"
                                    >
                                        {item.value}
                                    </Text>
                                </ParishCardContent>
                            </ParishCard>
                        ))}
                    </section>

                    <Text as="p" variant="fine-print" color="muted">
                        Halaman ini adalah placeholder fondasi. Situs publik
                        lengkap dibangun pada phase Beranda berikutnya.
                    </Text>
                </div>
            </PublicSection>
        </>
    );
}
