import { Link } from '@inertiajs/react';
import { InertiaLinkProps } from '@inertiajs/react';
import { Pencil, Trash } from 'lucide-react';

export type RowActionsProps = {
    canUpdate: boolean;
    canDelete: boolean;
    updateHref?: InertiaLinkProps['href'];
    onDelete: () => void;
};

export function RowActions({
    canUpdate,
    canDelete,
    updateHref,
    onDelete,
}: RowActionsProps) {
    if (!canUpdate && !canDelete) {
        return null;
    }

    return (
        <div className="flex items-center justify-start gap-4">
            {canUpdate && updateHref ? (
                <Link
                    href={updateHref}
                    aria-label="Ubah"
                    title="Ubah"
                    className="rounded-sm text-primary hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                >
                    <Pencil className="size-4" aria-hidden="true" />
                </Link>
            ) : null}

            {canDelete ? (
                <button
                    type="button"
                    onClick={onDelete}
                    aria-label="Hapus"
                    title="Hapus"
                    className="rounded-sm text-red-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                >
                    <Trash className="size-4" aria-hidden="true" />
                </button>
            ) : null}
        </div>
    );
}
