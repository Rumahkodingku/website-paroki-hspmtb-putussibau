import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { ParishCard, ParishCardContent } from '@/components/parish-card';
import { PublicSection } from '@/components/public-section';
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
                        <p className="text-caption-strong text-red-on-surface">
                            Paroki Hati Santa Perawan Maria Tak Bernoda
                        </p>

                        <h1 className="max-w-3xl font-display text-display-md text-balance">
                            [ISI: kalimat sambutan paroki]
                        </h1>

                        <p className="max-w-2xl text-body text-muted-foreground">
                            [ISI: ringkasan singkat siapa kami dan mengapa situs
                            ini ada. Dipilih agar pengunjung baru memahami
                            sebelum menjelajah.]
                        </p>

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
                                    <p className="text-caption-strong text-muted-foreground">
                                        {item.label}
                                    </p>
                                    <p className="text-body text-foreground">
                                        {item.value}
                                    </p>
                                </ParishCardContent>
                            </ParishCard>
                        ))}
                    </section>

                    <p className="text-fine-print text-muted-foreground">
                        Halaman ini adalah placeholder fondasi. Situs publik
                        lengkap dibangun pada phase Beranda berikutnya.
                    </p>
                </div>
            </PublicSection>
        </>
    );
}
