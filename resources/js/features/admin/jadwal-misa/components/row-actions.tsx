import { Link } from '@inertiajs/react';
import { InertiaLinkProps } from '@inertiajs/react';

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
        <div className="flex items-center justify-end gap-4">
            {canUpdate && updateHref ? (
                <Link
                    href={updateHref}
                    className="rounded-sm text-primary hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                >
                    Ubah
                </Link>
            ) : null}

            {canDelete ? (
                <button
                    type="button"
                    onClick={onDelete}
                    className="rounded-sm text-red-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                >
                    Hapus
                </button>
            ) : null}
        </div>
    );
}
