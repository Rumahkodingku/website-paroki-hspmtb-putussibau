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
    /** Controls the open state so the page decides when to show it. */
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description: ReactNode;
    /** Label for the action that carries the change through. */
    confirmLabel: string;
    cancelLabel?: string;
    /**
     * Marks the action as irreversible. Rendered with the destructive
     * treatment so the consequence is visible before the click.
     */
    destructive?: boolean;
    disabled?: boolean;
    onConfirm: () => void;
};

/**
 * Confirmation for an action that cannot be undone.
 *
 * Built on the Dialog primitive that is already in the project rather than
 * AlertDialog, which keeps the shadcn dependency list unchanged. The behaviour
 * is the same: focus is trapped, Escape closes, and cancelling is the default
 * focused control.
 *
 * The trigger is the caller's job. That keeps the button that opens the dialog
 * next to whatever it acts on, instead of this component deciding where it
 * belongs.
 *
 * @see docs/DESIGN.md (button-primary, destructive treatment)
 */
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
            <DialogContent className="sm:max-w-md">
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
