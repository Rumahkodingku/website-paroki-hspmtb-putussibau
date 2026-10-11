import { ConfirmDialog } from '@/components/confirm-dialog';
import { Text } from '@/components/text';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    itemLabel: string;
    consequences: string[];
    isSubmitting: boolean;
    onConfirm: () => void;
};

export function DeleteDialog({
    open,
    onOpenChange,
    itemLabel,
    consequences,
    isSubmitting,
    onConfirm,
}: Props) {
    return (
        <ConfirmDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Hapus data"
            destructive
            disabled={isSubmitting}
            confirmLabel="Hapus"
            description={
                <>
                    <Text as="p" variant="body">
                        {itemLabel} akan dihapus dari daftar ini dan tidak lagi
                        tampil di halaman publik.
                    </Text>

                    {consequences.length > 0 ? (
                        <Text as="p" variant="body" className="mt-3">
                            Yang ikut hilang: {consequences.join(', ')}.
                        </Text>
                    ) : null}

                    <Text
                        as="p"
                        variant="caption"
                        color="muted"
                        className="mt-3"
                    >
                        Tindakan ini tidak dapat dibatalkan dari halaman ini.
                        Bila hanya ingin menyembunyikan tanpa menghapus, gunakan
                        status nonaktif.
                    </Text>
                </>
            }
            onConfirm={onConfirm}
        />
    );
}
