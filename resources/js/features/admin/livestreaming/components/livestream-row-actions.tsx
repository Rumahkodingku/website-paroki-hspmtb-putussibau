import { Link } from '@inertiajs/react';
import {
    Ban,
    Pencil,
    Play,
    Square,
    Trash2,
    type LucideIcon,
} from 'lucide-react';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { edit as editLivestream } from '@/routes/livestreams';
import type { LivestreamRow } from '../types';

type ActionButtonProps = {
    label: string;
    icon: LucideIcon;
    onClick: () => void;
    testId: string;
    destructive?: boolean;
};

function ActionButton({
    label,
    icon: Icon,
    onClick,
    testId,
    destructive = false,
}: ActionButtonProps) {
    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <button
                    type="button"
                    onClick={onClick}
                    aria-label={label}
                    data-test={testId}
                    className={
                        destructive
                            ? 'cursor-pointer rounded-sm text-destructive hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none'
                            : 'cursor-pointer rounded-sm text-primary-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none'
                    }
                >
                    <Icon className="size-4" aria-hidden="true" />
                </button>
            </TooltipTrigger>

            <TooltipContent>
                <p>{label}</p>
            </TooltipContent>
        </Tooltip>
    );
}

type Props = {
    stream: LivestreamRow;
    onStart: () => void;
    onEnd: () => void;
    onUnavailable: () => void;
    onDelete: () => void;
};

/**
 * The one place that decides which operational actions a row offers.
 *
 * `can.update` comes from the policy; the status checks only decide whether the
 * action makes sense right now. The server enforces both again.
 */
export function LivestreamRowActions({
    stream,
    onStart,
    onEnd,
    onUnavailable,
    onDelete,
}: Props) {
    const canUpdate = stream.can.update;
    const canStart =
        canUpdate &&
        stream.status === 'published' &&
        (stream.broadcast_status === 'scheduled' ||
            (stream.broadcast_status === 'unavailable' &&
                stream.ended_at === null));
    const canEnd = canUpdate && stream.broadcast_status === 'live';
    const canUnavailable =
        canUpdate &&
        stream.broadcast_status !== 'live' &&
        stream.broadcast_status !== 'unavailable';

    return (
        <div className="flex items-center gap-4">
            {canUpdate ? (
                <Tooltip>
                    <TooltipTrigger asChild>
                        <Link
                            href={editLivestream(stream.id)}
                            aria-label="Ubah"
                            data-test="livestream-edit"
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

            {canStart ? (
                <ActionButton
                    label="Mulai siaran"
                    icon={Play}
                    onClick={onStart}
                    testId="livestream-start"
                />
            ) : null}

            {canEnd ? (
                <ActionButton
                    label="Akhiri siaran"
                    icon={Square}
                    onClick={onEnd}
                    testId="livestream-end"
                />
            ) : null}

            {canUnavailable ? (
                <ActionButton
                    label="Tandai tidak tersedia"
                    icon={Ban}
                    onClick={onUnavailable}
                    testId="livestream-unavailable"
                />
            ) : null}

            {stream.can.delete ? (
                <ActionButton
                    label="Hapus"
                    icon={Trash2}
                    onClick={onDelete}
                    testId="livestream-delete"
                    destructive
                />
            ) : null}
        </div>
    );
}
