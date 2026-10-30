import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, RefreshCw } from 'lucide-react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { database, show } from '@/routes/install';
import { check } from '@/routes/install/requirements';
import { useState } from 'react';
import RequirementRow from '../components/requirement-row';
import StepShell from '../components/step-shell';
import type { RequirementCheck } from '../types';

type Props = {
    checks: RequirementCheck[];
    passed: boolean;
};

export default function Requirements({ checks, passed }: Props) {
    const [checking, setChecking] = useState(false);

    const failed = checks.filter((check) => check.status === 'fail').length;
    const warnings = checks.filter(
        (check) => check.status === 'warning',
    ).length;

    const recheck = () => {
        setChecking(true);

        router.post(
            check().url,
            {},
            {
                preserveScroll: true,
                onFinish: () => setChecking(false),
            },
        );
    };

    return (
        <>
            <Head title="Persyaratan Sistem" />

            <StepShell
                eyebrow="Langkah 2 dari 8"
                title="Persyaratan sistem"
                description="Pemeriksaan ini hanya membaca kondisi server. Tidak ada tabel atau data yang dibuat."
                footer={
                    <>
                        <Button variant="ghost" asChild className="text-sm">
                            <Link href={show()}>
                                <ArrowLeft aria-hidden="true" />
                                Kembali
                            </Link>
                        </Button>

                        <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={recheck}
                                disabled={checking}
                                className="text-sm"
                            >
                                {checking ? (
                                    <Spinner aria-hidden="true" />
                                ) : (
                                    <RefreshCw aria-hidden="true" />
                                )}
                                Periksa Ulang
                            </Button>

                            {passed ? (
                                <Button asChild className="text-sm">
                                    <Link href={database()}>
                                        Lanjutkan
                                        <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            ) : (
                                <Button disabled className="text-sm">
                                    Lanjutkan
                                    <ArrowRight aria-hidden="true" />
                                </Button>
                            )}
                        </div>
                    </>
                }
            >
                <div className="flex flex-col gap-4">
                    <div className="flex flex-wrap items-center gap-x-6 gap-y-2 rounded-xl bg-muted px-4 py-3">
                        <Text as="p" variant="body-sm" color="muted">
                            {checks.length} pemeriksaan
                        </Text>
                        <Text as="p" variant="body-sm" color="muted">
                            {failed} gagal
                        </Text>
                        <Text as="p" variant="body-sm" color="muted">
                            {warnings} peringatan
                        </Text>
                    </div>

                    <ul className="divide-y divide-border">
                        {checks.map((check) => (
                            <RequirementRow key={check.key} check={check} />
                        ))}
                    </ul>

                    {!passed ? (
                        <Text
                            as="p"
                            variant="body-sm"
                            className="text-primary-on-surface"
                        >
                            Perbaiki item yang gagal, lalu tekan Periksa Ulang.
                            Tombol Lanjutkan aktif setelah seluruh pemeriksaan
                            wajib lulus.
                        </Text>
                    ) : null}
                </div>
            </StepShell>
        </>
    );
}
