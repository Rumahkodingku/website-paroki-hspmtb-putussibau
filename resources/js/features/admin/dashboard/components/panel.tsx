import type { ReactNode } from 'react';
import { Text } from '@/components/text';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { cn } from '@/lib/utils';

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
