import { router } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import type { FlashToast } from '@/types/ui';

export function useFlashToast(): void {
    useEffect(() => {
        return router.on('flash', (event) => {
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
    }, []);
}
