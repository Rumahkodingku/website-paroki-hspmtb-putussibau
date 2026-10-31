import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, Home, RefreshCw } from 'lucide-react';
import Image from '@/components/image';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { home, login } from '@/routes';

type Props = {
    status: number;
};

type Copy = {
    title: string;
    description: string;
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
                {COPY[status] ? (
                    <div className="w-full max-w-220">
                        <Image
                            src={`/illustrations/error/${status}.webp`}
                            alt=""
                            aspectRatio="2 / 1"
                            fit="contain"
                            priority
                            className="w-full"
                        />
                    </div>
                ) : null}

                <div className="mx-auto grid gap-4 text-center">
                    <Text as="h1" variant="display-xl" color="danger">
                        {status}
                    </Text>

                    <Text as="h1" variant="h1">
                        {copy.title}
                    </Text>

                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        className="max-w-xl"
                    >
                        {copy.description}
                    </Text>
                </div>

                <div className="mx-auto flex flex-wrap items-center gap-3">
                    <Button asChild size="lg" className="text-sm">
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
