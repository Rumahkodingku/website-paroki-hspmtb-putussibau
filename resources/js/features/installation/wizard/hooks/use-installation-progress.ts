import { useEffect, useRef, useState } from 'react';
import { getJson } from '../lib/csrf';
import { progress as progressRoute } from '@/routes/install';
import type { InstallationErrorPayload, ProgressState } from '../types';

type Options = {
    initialProgress: ProgressState;
    initialRunning: boolean;
    onCompleted: () => void;
    onFailed: (error: InstallationErrorPayload) => void;
};

/**
 * Polls the backend progress endpoint while an installation is running.
 * Callbacks are stored in refs so the polling effect does not restart when
 * the parent re-renders.
 */
export function useInstallationProgress({
    initialProgress,
    initialRunning,
    onCompleted,
    onFailed,
}: Options) {
    const [progress, setProgress] = useState(initialProgress);
    const [running, setRunning] = useState(initialRunning);

    const onCompletedRef = useRef(onCompleted);
    const onFailedRef = useRef(onFailed);

    useEffect(() => {
        onCompletedRef.current = onCompleted;
        onFailedRef.current = onFailed;
    });

    useEffect(() => {
        if (!running) {
            return;
        }

        let active = true;

        const tick = async () => {
            try {
                const response = await getJson(progressRoute().url);

                if (!response.ok) {
                    return;
                }

                const data = (await response.json()) as ProgressState & {
                    message?: string | null;
                };

                if (!active) {
                    return;
                }

                setProgress(data);

                if (data.status === 'completed') {
                    active = false;
                    setRunning(false);
                    onCompletedRef.current();
                } else if (data.status === 'failed') {
                    active = false;
                    setRunning(false);
                    onFailedRef.current({
                        code: data.error_code ?? 'INSTALL_FAILED',
                        message:
                            data.message ??
                            'Instalasi gagal. Periksa log server.',
                    });
                }
            } catch {
                // A transient poll failure is not a failed installation; the
                // next tick reads the same backend state.
            }
        };

        void tick();
        const interval = window.setInterval(() => void tick(), 1000);

        return () => {
            active = false;
            window.clearInterval(interval);
        };
    }, [running]);

    return { progress, setProgress, running, setRunning };
}
