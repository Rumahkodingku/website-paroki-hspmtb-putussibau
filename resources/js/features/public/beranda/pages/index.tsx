import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { dashboard, login } from '@/routes';

/*
 * Guest entry point.
 *
 * The public parish website is built in a later phase, and Phase 01 section 4
 * puts the final homepage out of scope. This page exists so the route is
 * reachable and so PublicLayout has something to render, so it shows the
 * structure DESIGN.md describes with placeholders rather than inventing parish
 * content. DESIGN.md is explicit: do not invent parish history, clergy names,
 * schedules, statistics, or pastoral claims, and neither does this repo.
 */
export default function Welcome() {
    const { auth } = usePage().props;

    return (
        <>
            <Head title="Selamat Datang" />

            <div className="mx-auto flex w-full max-w-7xl flex-col gap-12 px-4 py-12 md:py-20">
                <section className="space-y-6">
                    <p className="text-caption-strong text-primary">
                        Paroki Hati Santa Perawan Maria Tak Bernoda
                    </p>

                    <h1 className="max-w-3xl font-display text-display-md text-balance">
                        [ISI: kalimat sambutan paroki]
                    </h1>

                    <p className="max-w-2xl text-body text-muted-foreground">
                        [ISI: ringkasan singkat siapa kami dan mengapa situs ini
                        ada. Dipilih agar pengunjung baru memahami sebelum
                        menjelajah.]
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

                        <Button asChild size="lg" variant="outline">
                            <a href="#">[ISI: tautan jadwal misa]</a>
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
                        <div
                            key={item.label}
                            className="rounded-lg border border-hairline bg-card p-6"
                        >
                            <p className="text-caption-strong text-muted-foreground">
                                {item.label}
                            </p>
                            <p className="mt-2 text-body text-foreground">
                                {item.value}
                            </p>
                        </div>
                    ))}
                </section>

                <p className="text-fine-print text-muted-foreground">
                    Halaman ini adalah placeholder fondasi. Situs publik lengkap
                    dibangun pada phase UI/public website berikutnya.
                </p>
            </div>
        </>
    );
}
