import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';
import { showMutationErrorToast } from '@/lib/mutation-error-toast';

export function useFlashToast(): void {
    useEffect(() => {
        const offFlash = router.on('flash', (event) => {
            const detail = (
                event as CustomEvent<{ flash?: { toast?: FlashToast } }>
            ).detail;
            const flash = detail?.flash;
            const data = flash?.toast;

            if (!data) {
                return;
            }

            toast[data.type](data.message);
        });

        const offError = router.on('error', (event) => {
            const detail = event.detail as {
                errors?: Record<string, unknown>;
                page?: { url?: string };
            };
            const url = detail?.page?.url ?? window.location.pathname;

            // Halaman login punya toast khusus (kredensial/throttle); jangan dobel.
            if (url.includes('/login')) {
                return;
            }

            showMutationErrorToast(detail?.errors ?? {});
        });

        return () => {
            offFlash();
            offError();
        };
    }, []);
}
