import { Text } from '@/components/text';
import type { PublicServiceStep } from '../types';

export function StepsTimeline({ steps }: { steps: PublicServiceStep[] }) {
    return (
        <ol className="mt-6 space-y-6">
            {steps.map((step, index) => (
                <li
                    key={step.id}
                    className="relative flex min-w-0 items-start gap-4"
                >
                    {index < steps.length - 1 ? (
                        <span
                            aria-hidden="true"
                            className="absolute top-9 -bottom-6 left-4 w-px bg-border"
                        />
                    ) : null}

                    <span
                        aria-hidden="true"
                        className="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-navy text-xs font-semibold text-white tabular-nums"
                    >
                        {String(index + 1).padStart(2, '0')}
                    </span>

                    <div className="min-w-0 flex-1 pt-0.5">
                        <Text
                            as="h3"
                            variant="heading-sm"
                            className="leading-snug"
                        >
                            {step.title}
                        </Text>

                        {step.description ? (
                            <Text
                                as="p"
                                variant="body-sm"
                                color="muted"
                                className="mt-1.5 leading-relaxed"
                            >
                                {step.description}
                            </Text>
                        ) : null}
                    </div>
                </li>
            ))}
        </ol>
    );
}
