import Text from '@/components/text';
import type { ReactNode } from 'react';

export function CommunityFormPanel({
    children,
    title,
    description,
}: {
    children: ReactNode;
    title: string;
    description: string;
}) {
    return (
        <div>
            <div className="mb-10 space-y-1">
                <Text as="h3" variant="h3">
                    {title}
                </Text>
                <Text as="p" variant="body-sm" color="muted">
                    {description}
                </Text>
            </div>
            {children}
        </div>
    );
}
