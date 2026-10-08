import { Head, Link, router, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, Home, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { home, login } from '@/routes';

/*
 * Error page for 403, 404, 419, 500 and 503.
 *
 * PRD XC-E2 requires 404 and 500 in Bahasa Indonesia inside the public layout
 * with a link back to the home page. 403 and 419 are here as well because this
 * application produces both: a signed-in user without a role gets 403 on
 * /admin, and an expired session gets 419. An Indonesian administrator would
 * otherwise meet an English page for a case they will actually hit.
 *
 * Every string lives here rather than coming from the server. The exception
 * handler sends only the status, and that is the whole reason this component
 * cannot leak anything: there is no message from the exception to render, and
 * no detail to redact. A stack trace in a production 500 is not a matter of
 * hiding a field, it is a matter of the field not existing.
 *
 * Every status gets a way onward. A 404 with no link out is the one people
 * describe as a dead end, and 500 gets a retry because the failure is very
 * often already gone by the time the page renders.
 *
 * @see docs/DECISIONS.md D-26
 */

type Props = {
    status: number;
};

type Copy = {
    title: string;
    description: string;
    /** Whether reloading the same address could plausibly help. */
    retry?: boolean;
};

const COPY: Record<number, Copy> = {
    403: {
        title: 'Akses Ditolak',
        description:
            'Anda tidak memiliki izin untuk membuka halaman ini. Jika Anda merasa ini keliru, hubungi sekretariat paroki.',
    },
    404: {
        title: 'Halaman Tidak Ditemukan',
        description:
            'Alamat yang Anda buka tidak tersedia. Mungkin halaman tersebut dipindahkan, atau memang tidak pernah ada.',
    },
    419: {
        title: 'Sesi Kedaluwarsa',
        description:
            'Sesi Anda sudah berakhir, biasanya karena halaman lain dibuka terlalu lama. Muat ulang untuk melanjutkan.',
        retry: true,
    },
    500: {
        title: 'Terjadi Kesalahan',
        description:
            'Ada gangguan di sisi kami. Masalah ini sudah dicatat dan akan diperiksa.',
        retry: true,
    },
    503: {
        title: 'Sedang Dalam Pemeliharaan',
        description:
            'Situs sedang dalam pemeliharaan singkat. Silakan kembali beberapa saat lagi.',
        retry: true,
    },
};

/**
 * An unknown status is still a real status, and the page has to say something
 * useful rather than render an empty shell.
 */
const FALLBACK: Copy = {
    title: 'Halaman Tidak Dapat Ditampilkan',
    description:
        'Ada yang tidak beres saat membuka halaman ini. Silakan kembali ke beranda.',
};

export default function ErrorPage({ status }: Props) {
    const { auth } = usePage<{ auth: { user: { id: number } | null } }>().props;

    const copy = COPY[status] ?? FALLBACK;

    return (
        <>
            <Head title={`${copy.title} (${status})`} />

            <div className="mx-auto flex w-full max-w-3xl flex-col gap-8 px-4 py-16 md:py-24">
                <div className="grid gap-4">
                    <div className="flex items-center gap-3">
                        <span
                            aria-hidden="true"
                            className="inline-flex size-11 items-center justify-center rounded-full bg-primary-light text-primary"
                        >
                            <AlertTriangle className="size-5" />
                        </span>

                        <p className="text-caption-strong text-primary">
                            Kode {status}
                        </p>
                    </div>

                    <h1 className="font-display text-display-md text-balance">
                        {copy.title}
                    </h1>

                    <p className="max-w-xl text-body text-muted-foreground">
                        {copy.description}
                    </p>
                </div>

                <div className="flex flex-wrap items-center gap-3">
                    <Button asChild size="lg">
                        <Link href={home()}>
                            <Home />
                            Kembali ke Beranda
                        </Link>
                    </Button>

                    {copy.retry ? (
                        <Button
                            type="button"
                            size="lg"
                            variant="outline"
                            onClick={() => router.reload()}
                        >
                            <RefreshCw />
                            Coba Lagi
                        </Button>
                    ) : null}

                    {/*
                        Only a guest is offered a way to sign in. Someone already
                        signed in who cannot open a page is not going to be let in
                        by logging in again, and offering it implies the opposite.
                    */}
                    {status === 403 && !auth.user ? (
                        <Button asChild size="lg" variant="ghost">
                            <Link href={login()}>
                                <ArrowLeft />
                                Masuk
                            </Link>
                        </Button>
                    ) : null}
                </div>
            </div>
        </>
    );
}
