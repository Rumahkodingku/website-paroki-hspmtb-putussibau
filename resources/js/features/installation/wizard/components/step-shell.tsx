import type { ReactNode } from 'react';
import { Text } from '@/components/text';
import { Card, CardContent } from '@/components/ui/card';

/**
 * Shared frame for every wizard step: one card, one heading, one action row.
 */
export default function StepShell({
    eyebrow,
    title,
    description,
    children,
    footer,
}: {
    eyebrow?: string;
    title: string;
    description?: string;
    children: ReactNode;
    footer?: ReactNode;
}) {
    return (
        <Card className="border-none bg-transparent shadow-none">
            <CardContent>
                <div className="flex flex-col gap-2">
                    {eyebrow ? (
                        <Text as="p" variant="overline" color="muted">
                            {eyebrow}
                        </Text>
                    ) : null}

                    <Text as="h1" variant="h2">
                        {title}
                    </Text>

                    {description ? (
                        <Text
                            as="p"
                            variant="body"
                            color="muted"
                            leading="relaxed"
                            className="max-w-prose"
                        >
                            {description}
                        </Text>
                    ) : null}
                </div>

                <div className="mt-6">{children}</div>

                {footer ? (
                    <div className="mt-8 flex flex-col-reverse gap-3 border-t border-border pt-6 sm:flex-row sm:items-center sm:justify-between">
                        {footer}
                    </div>
                ) : null}
            </CardContent>
        </Card>
    );
}
