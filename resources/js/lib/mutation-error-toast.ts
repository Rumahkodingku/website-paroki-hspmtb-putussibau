import { toast } from 'sonner';

export function showMutationErrorToast(
    errors: Record<string, unknown>,
    title = 'Gagal menyimpan',
    fallback = 'Periksa isian dan coba lagi.',
): void {
    const first = Object.values(errors)
        .flat()
        .find((value) => typeof value === 'string' && value.trim() !== '');

    toast.error(title, {
        description: typeof first === 'string' ? first : fallback,
    });
}
