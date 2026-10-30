import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Church,
    Database,
    ServerCog,
    ShieldCheck,
    TriangleAlert,
    UserRound,
} from 'lucide-react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { requirements } from '@/routes/install';
import StepShell from '../components/step-shell';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';

const SUMMARY = [
    {
        icon: ServerCog,
        title: 'Persyaratan sistem',
        description:
            'PHP, ekstensi, direktori, dan aset frontend diperiksa tanpa menyentuh database.',
    },
    {
        icon: Database,
        title: 'Database',
        description:
            'Koneksi diuji dan database dipastikan masih kosong sebelum migrasi dijalankan.',
    },
    {
        icon: UserRound,
        title: 'Akun Super Admin',
        description:
            'Akun pertama dibuat pada langkah terakhir, bukan saat formulir diisi.',
    },
    {
        icon: Church,
        title: 'Identitas paroki',
        description:
            'Nama paroki, nama singkat, tagline, deskripsi, dan patron santo.',
    },
    {
        icon: ShieldCheck,
        title: 'Verifikasi dan penguncian',
        description:
            'Instalasi diverifikasi ulang sebelum dinyatakan selesai dan dikunci.',
    },
];

export default function Welcome() {
    return (
        <>
            <Head title="Instalasi Website" />

            <StepShell
                eyebrow="Langkah 1 dari 8"
                title="Selamat datang di Website Paroki HSPMTB Putussibau"
                description="Wizard ini menyiapkan instalasi baru: memeriksa server, menyiapkan database, membuat akun Super Admin, dan menyimpan identitas paroki. Tidak ada konten contoh yang dimasukkan."
                footer={
                    <>
                        <Text
                            as="p"
                            variant="caption"
                            color="muted"
                            className="max-w-sm"
                        >
                            Perkiraan waktu: beberapa menit, tergantung
                            kecepatan server.
                        </Text>

                        <Button asChild size="lg" className="text-sm">
                            <Link href={requirements()}>
                                Mulai Instalasi
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    </>
                }
            >
                <div className="flex flex-col gap-5">
                    <ol className="flex flex-col gap-6">
                        {SUMMARY.map((item) => {
                            const Icon = item.icon;

                            return (
                                <li key={item.title} className="flex gap-3">
                                    <span className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-muted text-primary-on-surface">
                                        <Icon
                                            aria-hidden="true"
                                            className="size-5"
                                        />
                                    </span>

                                    <div className="flex flex-col gap-0.5">
                                        <Text
                                            as="span"
                                            variant="body"
                                            weight="semibold"
                                        >
                                            {item.title}
                                        </Text>
                                        <Text
                                            as="span"
                                            variant="body-sm"
                                            color="muted"
                                            leading="relaxed"
                                        >
                                            {item.description}
                                        </Text>
                                    </div>
                                </li>
                            );
                        })}
                    </ol>

                    <Alert className="border-amber-500/30 bg-amber-500/10 text-amber-900 dark:text-amber-200">
                        <TriangleAlert />
                        <AlertTitle className="font-bold">
                            Hanya untuk instalasi baru.
                        </AlertTitle>
                        <AlertDescription className="text-amber-900 dark:text-amber-200">
                            Jangan jalankan wizard ini pada website yang sudah
                            berisi data. Wizard tidak menyediakan fitur
                            reinstall dan tidak menghapus data.
                        </AlertDescription>
                    </Alert>
                </div>
            </StepShell>
        </>
    );
}
