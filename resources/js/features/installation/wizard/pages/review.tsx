import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, CheckCircle2, Clock, Info } from 'lucide-react';
import { toast } from 'sonner';
import { useCallback, useRef, useState } from 'react';
import AlertError from '@/components/alert-error';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { application, complete } from '@/routes/install';
import ErrorPanel from '../components/error-panel';
import ProgressList from '../components/progress-list';
import ReviewSummary from '../components/review-summary';
import StepShell from '../components/step-shell';
import { useInstallationProgress } from '../hooks/use-installation-progress';
import { runInstallation } from '../lib/run-installation';
import type {
    InstallationErrorPayload,
    InstallationStatus,
    ProgressState,
    ReviewSummary as ReviewSummaryData,
} from '../types';

type Props = {
    status: InstallationStatus;
    progress: ProgressState;
    error: InstallationErrorPayload | null;
    summary: ReviewSummaryData | null;
};

export default function Review({ status, progress, error, summary }: Props) {
    const [errorState, setErrorState] =
        useState<InstallationErrorPayload | null>(error);
    const [confirmed, setConfirmed] = useState(false);
    const [requestError, setRequestError] = useState<string | null>(null);
    const [showSuccess, setShowSuccess] = useState(false);
    const navigating = useRef(false);

    const goToComplete = useCallback(() => {
        if (navigating.current) {
            return;
        }

        navigating.current = true;

        toast.success('Instalasi berhasil', {
            description: 'Mengalihkan ke halaman selesai...',
        });

        const prefersReducedMotion =
            typeof window !== 'undefined' &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (prefersReducedMotion) {
            router.visit(complete().url);
            return;
        }

        setShowSuccess(true);
        window.setTimeout(() => {
            router.visit(complete().url);
        }, 800);
    }, []);

    const handleFailed = useCallback(
        (failedError: InstallationErrorPayload) => {
            setErrorState(failedError);
            toast.error('Instalasi gagal', {
                description: failedError.message,
            });
        },
        [],
    );

    const {
        progress: state,
        setProgress: setState,
        running,
        setRunning,
    } = useInstallationProgress({
        initialProgress: progress,
        initialRunning: status === 'installing',
        onCompleted: goToComplete,
        onFailed: handleFailed,
    });

    const start = useCallback(() => {
        void runInstallation(true, {
            setRunning,
            setProgress: setState,
            setError: setErrorState,
            setRequestError,
            onCompleted: goToComplete,
        });
    }, [setRunning, setState, goToComplete]);

    if (showSuccess) {
        return (
            <>
                <Head title="Instalasi Berhasil" />

                <StepShell
                    eyebrow="Langkah 6 dari 7"
                    title="Instalasi berhasil"
                    description="Hasil instalasi sedang disimpan. Anda akan dialihkan sebentar lagi."
                >
                    <div className="flex flex-col items-center gap-4 py-8 motion-safe:animate-in motion-safe:duration-300 motion-safe:fade-in motion-reduce:animate-none">
                        <CheckCircle2
                            aria-hidden="true"
                            className="size-16 text-emerald-600 motion-safe:animate-in motion-safe:duration-300 motion-safe:zoom-in-50 dark:text-emerald-400"
                        />

                        <Text as="p" variant="body" color="muted">
                            Menyimpan hasil dan membuka halaman selesai...
                        </Text>
                    </div>
                </StepShell>
            </>
        );
    }

    if (running || (state.status === 'installing' && !requestError)) {
        return (
            <>
                <Head title="Instalasi Berjalan" />

                <StepShell
                    eyebrow="Langkah 6 dari 7"
                    title="Instalasi sedang berjalan"
                    description="Jangan tutup halaman ini. Status di bawah berasal dari backend, bukan perkiraan dari browser."
                    footer={
                        <>
                            <div className="flex items-center gap-2">
                                <Clock
                                    aria-hidden="true"
                                    className="size-4 text-muted-foreground"
                                />
                                <Text as="p" variant="caption" color="muted">
                                    {state.started_at
                                        ? `Dimulai ${new Date(state.started_at).toLocaleTimeString('id-ID')}`
                                        : 'Menunggu proses dimulai'}
                                </Text>
                            </div>

                            <Text
                                as="p"
                                variant="caption"
                                color="muted"
                                className="max-w-xs"
                            >
                                Bila proses melebihi beberapa menit, periksa
                                file status dan log server sebelum mengulang.
                            </Text>
                        </>
                    }
                >
                    <ProgressList progress={state} />
                </StepShell>
            </>
        );
    }

    if (state.status === 'failed' || errorState) {
        return (
            <>
                <Head title="Instalasi Gagal" />

                <StepShell
                    eyebrow="Langkah 6 dari 7"
                    title="Instalasi tidak selesai"
                    description="Tidak ada data yang dihapus otomatis. Ikuti instruksi di bawah sebelum mencoba lagi."
                >
                    {errorState ? (
                        <ErrorPanel error={errorState} />
                    ) : (
                        <AlertError
                            title="Instalasi gagal"
                            errors={[
                                'Status instalasi tidak dapat dipastikan. Periksa file status dan log server.',
                            ]}
                        />
                    )}
                </StepShell>
            </>
        );
    }

    if (summary === null) {
        return (
            <>
                <Head title="Konfirmasi Instalasi" />

                <StepShell
                    eyebrow="Langkah 6 dari 7"
                    title="Data instalasi belum lengkap"
                    description="Kembali ke langkah sebelumnya dan lengkapi data yang diminta."
                    footer={
                        <Button asChild>
                            <Link href={application()}>
                                Kembali ke identitas paroki
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    }
                >
                    <AlertError
                        title="Sesi tidak lengkap"
                        errors={[
                            'Data database, Super Admin, atau identitas paroki tidak ditemukan pada sesi ini.',
                        ]}
                    />
                </StepShell>
            </>
        );
    }

    return (
        <>
            <Head title="Konfirmasi Instalasi" />

            <StepShell
                eyebrow="Langkah 6 dari 7"
                title="Periksa dan konfirmasi"
                description="Setelah tombol di bawah ditekan, wizard membuat tabel, menjalankan seeder produksi, membuat akun Super Admin, dan menyimpan identitas paroki."
                footer={
                    <>
                        <Button
                            variant="ghost"
                            asChild
                            disabled={!confirmed}
                            className="text-sm"
                        >
                            <Link href={application()}>
                                <ArrowLeft aria-hidden="true" />
                                Kembali
                            </Link>
                        </Button>

                        <Button
                            type="button"
                            size="lg"
                            onClick={start}
                            disabled={!confirmed}
                            className="text-sm"
                        >
                            Install Sekarang
                            <ArrowRight aria-hidden="true" />
                        </Button>
                    </>
                }
            >
                <div className="flex flex-col gap-6">
                    <ReviewSummary summary={summary} />

                    <div className="rounded-xl border border-amber-500/30 bg-amber-500/10 p-4">
                        <div className="flex gap-3">
                            <Info
                                aria-hidden="true"
                                className="mt-0.5 size-4 shrink-0 text-amber-700 dark:text-amber-300"
                            />

                            <Text
                                as="p"
                                variant="body-sm"
                                className="text-amber-900 dark:text-amber-200"
                            >
                                Pastikan database target benar-benar baru dan
                                tidak berisi data yang perlu dipertahankan.
                                Wizard tidak menyediakan rollback atau
                                penghapusan data.
                            </Text>
                        </div>
                    </div>

                    {requestError ? (
                        <AlertError
                            title="Instalasi tidak dapat dimulai"
                            errors={[requestError]}
                        />
                    ) : null}

                    <div className="flex items-start gap-3 rounded-xl border border-border p-4">
                        <Checkbox
                            id="confirm-fresh-database"
                            checked={confirmed}
                            onCheckedChange={(value) =>
                                setConfirmed(value === true)
                            }
                        />

                        <label
                            htmlFor="confirm-fresh-database"
                            className="text-body-sm leading-relaxed"
                        >
                            Saya memastikan database target adalah instalasi
                            baru dan tidak berisi data yang perlu dipertahankan.
                        </label>
                    </div>
                </div>
            </StepShell>
        </>
    );
}
