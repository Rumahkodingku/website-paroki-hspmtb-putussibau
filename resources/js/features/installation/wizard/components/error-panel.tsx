import { AlertTriangle } from 'lucide-react';
import { Text } from '@/components/text';
import type { InstallationErrorPayload } from '../types';

/**
 * What a failed installation tells the person in front of the screen.
 *
 * It names the code so an operator can find the matching log line, but it
 * never renders the exception, the query, or the connection string.
 */
export default function ErrorPanel({
    error,
}: {
    error: InstallationErrorPayload;
}) {
    return (
        <div className="rounded-2xl border border-destructive/30 bg-destructive/5 p-5 motion-safe:animate-in motion-safe:duration-200 motion-safe:fade-in motion-reduce:animate-none">
            <div className="flex items-start gap-3">
                <AlertTriangle
                    aria-hidden="true"
                    className="mt-0.5 size-5 shrink-0 text-primary-on-surface"
                />

                <div className="flex flex-col gap-2">
                    <Text as="h2" variant="h4">
                        Instalasi tidak diselesaikan
                    </Text>

                    <Text as="p" variant="body-sm" color="muted">
                        {error.message}
                    </Text>

                    <Text as="p" variant="caption" color="muted">
                        Kode kesalahan:{' '}
                        <span className="font-mono">{error.code}</span>
                    </Text>

                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        leading="relaxed"
                    >
                        Periksa log server untuk detail. Jangan menghapus tabel
                        atau menjalankan migrasi ulang secara manual sebelum
                        memastikan kondisi database. Setelah database
                        diverifikasi, operator dapat menghapus file status
                        instalasi melalui SSH untuk mengulang dari awal.
                    </Text>
                </div>
            </div>
        </div>
    );
}
