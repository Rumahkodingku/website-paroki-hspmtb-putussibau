import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { InstallationStep } from '@/types';

/**
 * The seven-step navigation on the installer's branding panel.
 *
 * The steps are a real sequence, so numbering them is information rather than
 * decoration. The dark panel is the only place this renders.
 */
export default function StepIndicator({
    steps,
    currentStep,
}: {
    steps: InstallationStep[];
    currentStep: number;
}) {
    return (
        <ol className="flex flex-col gap-1">
            {steps.map((step, index) => {
                const done = index < currentStep;
                const current = index === currentStep;

                return (
                    <li
                        key={step.key}
                        aria-current={current ? 'step' : undefined}
                        className={cn(
                            'flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors',
                            current && 'bg-white/10',
                        )}
                    >
                        <span
                            aria-hidden="true"
                            className={cn(
                                'flex size-6 shrink-0 items-center justify-center rounded-full border text-[11px] font-semibold',
                                done &&
                                    'border-white/30 bg-white/15 text-white/80',
                                current &&
                                    'border-white bg-white text-navy-deep',
                                !done &&
                                    !current &&
                                    'border-white/25 text-white/50',
                            )}
                        >
                            {done ? <Check className="size-3.5" /> : index + 1}
                        </span>

                        <span
                            className={cn(
                                'text-body-sm',
                                current && 'font-semibold text-white',
                                done && 'text-white/70',
                                !done && !current && 'text-white/45',
                            )}
                        >
                            {step.label}
                        </span>
                    </li>
                );
            })}
        </ol>
    );
}
