import type { ReactNode } from 'react';
import { Text } from '@/components/text';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { cn } from '@/lib/utils';

/*
 * The frame every dashboard panel shares: a titled header with an optional
 * action, and a body that is usually a divide-y list.
 *
 * One component because seven panels would otherwise write the same border,
 * padding and heading seven times, and the fourth copy is where one of them
 * drifts. The title is an h3 so the page keeps one h2 for its own heading.
 *
 * The body has no padding of its own so a list can run edge to edge and a
 * centred empty state can fill the panel; a caller that wants padding passes it
 * to its own content.
 */
export function Panel({
    title,
    action,
    children,
    className,
}: {
    title: string;
    action?: ReactNode;
    children: ReactNode;
    className?: string;
}) {
    return (
        <Card className={cn('gap-0 overflow-hidden py-0', className)}>
            <CardHeader className="flex flex-row items-center justify-between gap-3 border-b border-border px-5 py-4">
                <Text as="h3" variant="heading-sm">
                    {title}
                </Text>
                {action}
            </CardHeader>
            <CardContent className="px-0">{children}</CardContent>
        </Card>
    );
}
