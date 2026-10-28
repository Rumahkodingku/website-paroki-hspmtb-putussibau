import { Link } from '@inertiajs/react';
import { Download, Eye, Trash2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { download, show } from '@/routes/backups';
import type { BackupRow } from '../types';

export function BackupRowActions({
    backup,
    onDelete,
}: {
    backup: BackupRow;
    onDelete: () => void;
}) {
    return (
        <div className="flex items-center gap-1">
            <Tooltip>
                <TooltipTrigger asChild>
                    <Button
                        asChild
                        variant="ghost"
                        size="icon"
                        className="size-9"
                        aria-label="Lihat detail backup"
                    >
                        <Link href={show.url(backup.id)}>
                            <Eye className="size-4" aria-hidden="true" />
                        </Link>
                    </Button>
                </TooltipTrigger>
                <TooltipContent>Lihat detail</TooltipContent>
            </Tooltip>

            {backup.can.download ? (
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            asChild
                            variant="ghost"
                            size="icon"
                            className="size-9"
                            aria-label="Unduh backup"
                        >
                            <a href={download.url(backup.id)}>
                                <Download
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </a>
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Unduh</TooltipContent>
                </Tooltip>
            ) : null}

            {backup.can.delete ? (
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-9 text-primary-on-surface hover:bg-primary-soft"
                            onClick={onDelete}
                            aria-label="Hapus backup"
                        >
                            <Trash2 className="size-4" aria-hidden="true" />
                        </Button>
                    </TooltipTrigger>
                    <TooltipContent>Hapus</TooltipContent>
                </Tooltip>
            ) : null}
        </div>
    );
}
