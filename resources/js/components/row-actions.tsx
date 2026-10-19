import { Link } from '@inertiajs/react';
import { InertiaLinkProps } from '@inertiajs/react';
import { Pencil, Trash } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from './ui/tooltip';

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
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link
                            href={updateHref}
                            aria-label="Ubah"
                            className="cursor-pointer rounded-sm text-primary-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Pencil className="size-4" aria-hidden="true" />
                        </Link>
                    </TooltipTrigger>

                    <TooltipContent>
                        <p>Ubah</p>
                    </TooltipContent>
                </Tooltip>
            ) : null}

            {canDelete ? (
                <Tooltip>
                    <TooltipTrigger asChild>
                        <button
                            type="button"
                            onClick={onDelete}
                            aria-label="Hapus"
                            className="cursor-pointer rounded-sm text-primary-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Trash className="size-4" aria-hidden="true" />
                        </button>
                    </TooltipTrigger>

                    <TooltipContent>
                        <p>Hapus</p>
                    </TooltipContent>
                </Tooltip>
            ) : null}
        </div>
    );
}
