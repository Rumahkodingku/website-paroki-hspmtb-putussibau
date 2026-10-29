import { postJson } from './csrf';
import { run } from '@/routes/install';
import { toast } from 'sonner';
import type {
    InstallationErrorPayload,
    InstallationStatus,
    ProgressState,
} from '../types';

type RunCallbacks = {
    setRunning: (value: boolean) => void;
    setProgress: (updater: (previous: ProgressState) => ProgressState) => void;
    setError: (error: InstallationErrorPayload | null) => void;
    setRequestError: (message: string | null) => void;
    onCompleted: () => void;
};

type RunResponse = {
    status?: InstallationStatus;
    error_code?: string | null;
    message?: string;
};

/**
 * Triggers the long-running install request and maps its response onto the
 * wizard state. Kept separate so the page component stays focused on rendering.
 */
export async function runInstallation(
    confirmed: boolean,
    callbacks: RunCallbacks,
): Promise<void> {
    const { setRunning, setProgress, setError, setRequestError, onCompleted } =
        callbacks;

    setRequestError(null);
    setError(null);
    setRunning(true);
    setProgress((previous) => ({ ...previous, status: 'installing' }));

    try {
        const response = await postJson(run().url, { confirmed });
        const contentType = response.headers.get('content-type') ?? '';

        if (response.status === 429) {
            setRunning(false);
            setRequestError(
                'Terlalu banyak percobaan instalasi. Tunggu sebentar lalu coba lagi.',
            );
            toast.error('Terlalu banyak percobaan', {
                description: 'Tunggu sebentar lalu coba lagi.',
            });

            return;
        }

        if (!contentType.includes('application/json')) {
            setRunning(false);
            setRequestError(
                'Sesi berakhir atau server mengembalikan halaman yang tidak dikenali. Muat ulang halaman lalu isi kembali data bila diminta.',
            );
            toast.error('Sesi berakhir', {
                description:
                    'Muat ulang halaman lalu isi kembali data bila diminta.',
            });

            return;
        }

        const data = (await response.json()) as RunResponse;

        if (data.status === 'completed') {
            setRunning(false);
            onCompleted();

            return;
        }

        if (response.status === 409 && data.status === 'installing') {
            return;
        }

        setRunning(false);
        setError({
            code: data.error_code ?? 'INSTALL_FAILED',
            message:
                data.message ??
                'Instalasi gagal dimulai. Periksa status dan log server.',
        });
        toast.error('Instalasi gagal', {
            description:
                data.message ??
                'Instalasi gagal dimulai. Periksa status dan log server.',
        });
        setProgress((previous) => ({
            ...previous,
            status: 'failed',
            error_code: data.error_code ?? null,
        }));
    } catch {
        setRunning(false);
        setRequestError(
            'Koneksi ke server terputus. Periksa status instalasi sebelum mencoba lagi.',
        );
        toast.error('Koneksi terputus', {
            description: 'Periksa status instalasi sebelum mencoba lagi.',
        });
    }
}
