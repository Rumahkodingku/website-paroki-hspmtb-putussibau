import { Text } from '@/components/text';
import {
    REQUIREMENT_CONDITION_LABELS,
    getRequirementAudienceLabel,
} from '../lib/requirement-labels';
import type { PublicServiceRequirement } from '../types';

export function RequirementList({
    requirements,
}: {
    requirements: PublicServiceRequirement[];
}) {
    return (
        <ol className="mt-6 space-y-5">
            {requirements.map((requirement, index) => {
                const conditionLabel =
                    REQUIREMENT_CONDITION_LABELS[
                        requirement.requirement_condition
                    ] ?? 'Persyaratan';

                return (
                    <li
                        key={requirement.id}
                        className="flex min-w-0 items-start gap-3"
                    >
                        <span
                            aria-hidden="true"
                            className="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground tabular-nums"
                        >
                            {String(index + 1).padStart(2, '0')}
                        </span>

                        <div className="min-w-0 flex-1 pt-0.5">
                            <div className="mb-1.5 flex flex-wrap items-center gap-1.5">
                                <span
                                    className={[
                                        'inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold',
                                        requirement.requirement_condition ===
                                        'required'
                                            ? 'bg-primary/10 text-primary'
                                            : requirement.requirement_condition ===
                                                'conditional'
                                              ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/50 dark:text-amber-300'
                                              : 'bg-muted text-muted-foreground',
                                    ].join(' ')}
                                >
                                    {conditionLabel}
                                </span>

                                {requirement.applies_to ? (
                                    <span className="inline-flex items-center rounded-full border border-border bg-background px-2 py-0.5 text-[11px] font-medium text-muted-foreground">
                                        {getRequirementAudienceLabel(
                                            requirement.applies_to,
                                        )}
                                    </span>
                                ) : null}
                            </div>

                            <Text
                                as="p"
                                variant="body"
                                className="leading-relaxed"
                                weight="semibold"
                            >
                                {requirement.text}
                            </Text>

                            {requirement.note ? (
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="mt-1.5 leading-relaxed"
                                >
                                    {requirement.note}
                                </Text>
                            ) : null}
                        </div>
                    </li>
                );
            })}
        </ol>
    );
}
