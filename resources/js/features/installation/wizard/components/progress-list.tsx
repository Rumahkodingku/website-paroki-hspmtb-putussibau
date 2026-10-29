import { Check, Circle, Loader2, X } from 'lucide-react';
import { Text } from '@/components/text';
import { cn } from '@/lib/utils';
import { PROGRESS_STEPS, type ProgressState } from '../types';

type RowState = 'done' | 'running' | 'failed' | 'waiting';

const STATE_LABEL: Record<RowState, string> = {
    done: 'Selesai',
    running: 'Sedang berjalan',
    failed: 'Gagal',
    waiting: 'Menunggu',
};

export default function ProgressList({
    progress,
}: {
    progress: ProgressState;
}) {
    const currentIndex = progress.current_step
        ? PROGRESS_STEPS.findIndex((step) => step.key === progress.current_step)
        : -1;

    const failed = progress.status === 'failed';
    const completed = progress.status === 'completed';

    const rows = PROGRESS_STEPS.map((step, index) => {
        let state: RowState = 'waiting';

        if (completed || index < currentIndex) {
            state = 'done';
        } else if (index === currentIndex) {
            state = failed ? 'failed' : 'running';
        }

        return { ...step, state };
    });

    const active = rows.find((row) => row.state === 'running');

    const progressPercent = completed
        ? 100
        : currentIndex >= 0
          ? Math.round(
                ((currentIndex + (failed ? 0 : 0.5)) / PROGRESS_STEPS.length) *
                    100,
            )
          : 0;

    return (
        <div className="flex flex-col gap-4">
            <div aria-live="polite" aria-atomic="true" className="sr-only">
                {failed
                    ? 'Instalasi gagal.'
                    : completed
                      ? 'Instalasi selesai.'
                      : active
                        ? `Sedang berjalan: ${active.label}.`
                        : 'Menunggu proses instalasi dimulai.'}
            </div>

            <div
                className="h-1.5 w-full overflow-hidden rounded-full bg-muted motion-reduce:transition-none"
                role="presentation"
                aria-hidden="true"
            >
                <div
                    className="h-full rounded-full bg-primary transition-[width] duration-500 ease-out motion-reduce:transition-none"
                    style={{ width: `${progressPercent}%` }}
                />
            </div>

            <ol className="flex flex-col gap-1">
                {rows.map((row) => (
                    <li
                        key={row.key}
                        className={cn(
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 transition-all duration-300 ease-out motion-reduce:transition-none',
                            row.state === 'running' &&
                                'bg-muted motion-safe:animate-in motion-safe:duration-300 motion-safe:fade-in motion-safe:slide-in-from-left-2',
                        )}
                    >
                        <span className="flex size-6 shrink-0 items-center justify-center">
                            {row.state === 'done' ? (
                                <Check
                                    aria-hidden="true"
                                    className="size-4 text-emerald-600 motion-safe:animate-in motion-safe:duration-200 motion-safe:zoom-in-50 dark:text-emerald-400"
                                />
                            ) : row.state === 'running' ? (
                                <Loader2
                                    aria-hidden="true"
                                    className="size-4 animate-spin text-primary-on-surface motion-reduce:animate-none"
                                />
                            ) : row.state === 'failed' ? (
                                <X
                                    aria-hidden="true"
                                    className="size-4 text-primary-on-surface"
                                />
                            ) : (
                                <Circle
                                    aria-hidden="true"
                                    className="size-3 text-muted-foreground/40"
                                />
                            )}
                        </span>

                        <Text
                            as="span"
                            variant="body-sm"
                            weight={
                                row.state === 'running' ? 'semibold' : 'normal'
                            }
                            color={
                                row.state === 'waiting' ? 'muted' : 'default'
                            }
                            className="flex-1"
                        >
                            {row.label}
                        </Text>

                        <Text
                            as="span"
                            variant="caption"
                            color="muted"
                            className="shrink-0"
                        >
                            {STATE_LABEL[row.state]}
                        </Text>
                    </li>
                ))}
            </ol>
        </div>
    );
}
