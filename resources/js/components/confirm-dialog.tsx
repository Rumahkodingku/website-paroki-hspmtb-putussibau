import { type ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

type ConfirmDialogProps = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: ReactNode;
    confirmLabel: string;
    cancelLabel?: string;
    destructive?: boolean;
    disabled?: boolean;
    onConfirm: () => void;
};

export function ConfirmDialog({
    open,
    onOpenChange,
    title,
    description,
    confirmLabel,
    cancelLabel = 'Batal',
    destructive = false,
    disabled = false,
    onConfirm,
}: ConfirmDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{title}</DialogTitle>
                    <DialogDescription asChild>
                        <div className="text-sm text-muted-foreground">
                            {description}
                        </div>
                    </DialogDescription>
                </DialogHeader>

                <DialogFooter>
                    <DialogClose asChild>
                        <Button
                            type="button"
                            variant="outline"
                            data-test="confirm-dialog-cancel"
                        >
                            {cancelLabel}
                        </Button>
                    </DialogClose>

                    <Button
                        type="button"
                        variant={destructive ? 'destructive' : 'default'}
                        disabled={disabled}
                        data-test="confirm-dialog-confirm"
                        onClick={onConfirm}
                    >
                        {confirmLabel}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
