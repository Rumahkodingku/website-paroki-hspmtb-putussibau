import { Head, Link } from '@inertiajs/react';
import { ArrowUpRight, CheckCircle2, ListChecks, LogIn } from 'lucide-react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

type Props = {
    siteName: string;
    admin: { name: string; email: string };
    links: { home: string; login: string };
};

const NEXT_STEPS = [
    'Masuk ke panel administrasi dengan akun Super Admin.',
    'Periksa Pengaturan Situs dan lengkapi kontak, sosial, serta SEO.',
    'Tambahkan jadwal Misa, berita, agenda, pelayanan, komunitas, galeri, dan dokumen resmi.',
    'Periksa konfigurasi backup dan keamanan deployment.',
];

export default function Complete({ siteName, admin, links }: Props) {
    return (
        <>
            <Head title="Instalasi Berhasil" />

            <Card className="shadow-sm">
                <CardContent className="px-6 py-2 sm:px-8">
                    <div className="flex flex-col gap-6">
                        <div className="flex flex-col items-start gap-3">
                            <span className="flex size-12 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                                <CheckCircle2
                                    aria-hidden="true"
                                    className="size-6"
                                />
                            </span>

                            <Text as="h1" variant="h2">
                                Instalasi berhasil
                            </Text>

                            <Text
                                as="p"
                                variant="body"
                                color="muted"
                                leading="relaxed"
                                className="max-w-prose"
                            >
                                {siteName} sudah siap digunakan. Installer kini
                                terkunci dan tidak dapat dijalankan kembali
                                melalui HTTP.
                            </Text>
                        </div>

                        <dl className="divide-y divide-border border-y border-border">
                            <div className="grid gap-1 py-3 sm:grid-cols-[minmax(0,10rem)_minmax(0,1fr)] sm:gap-4">
                                <dt>
                                    <Text
                                        as="span"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        Super Admin
                                    </Text>
                                </dt>
                                <dd>
                                    <Text as="span" variant="body-sm">
                                        {admin.name}
                                    </Text>
                                </dd>
                            </div>

                            <div className="grid gap-1 py-3 sm:grid-cols-[minmax(0,10rem)_minmax(0,1fr)] sm:gap-4">
                                <dt>
                                    <Text
                                        as="span"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        Email
                                    </Text>
                                </dt>
                                <dd>
                                    <Text as="span" variant="body-sm">
                                        {admin.email}
                                    </Text>
                                </dd>
                            </div>
                        </dl>

                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Button asChild size="lg">
                                <Link href={links.login}>
                                    <LogIn aria-hidden="true" />
                                    Ke Halaman Login Admin
                                </Link>
                            </Button>

                            <Button variant="outline" size="lg" asChild>
                                <Link href={links.home}>
                                    Buka Website
                                    <ArrowUpRight aria-hidden="true" />
                                </Link>
                            </Button>
                        </div>

                        <div className="rounded-xl bg-muted p-5">
                            <div className="flex items-center gap-2">
                                <ListChecks
                                    aria-hidden="true"
                                    className="size-4 text-muted-foreground"
                                />
                                <Text as="h2" variant="h5">
                                    Tindakan selanjutnya
                                </Text>
                            </div>

                            <ol className="mt-3 flex list-inside list-decimal flex-col gap-2">
                                {NEXT_STEPS.map((step) => (
                                    <li key={step}>
                                        <Text
                                            as="span"
                                            variant="body-sm"
                                            color="muted"
                                            leading="relaxed"
                                        >
                                            {step}
                                        </Text>
                                    </li>
                                ))}
                            </ol>
                        </div>

                        <Text as="p" variant="fine-print" color="muted">
                            Setelah keluar dari halaman ini, installer tidak
                            dapat dibuka kembali. Untuk instalasi berikutnya,
                            gunakan database baru dan aktifkan kembali
                            INSTALLER_ENABLED.
                        </Text>
                    </div>
                </CardContent>
            </Card>
        </>
    );
}
