import { type ElementType, type ReactNode } from 'react';
import { cn } from '@/lib/utils';
import { PublicContainer } from '@/components/public-container';

export type PublicSectionProps = {
    children: ReactNode;
    size?: 'default' | 'large';
    surface?: 'default' | 'soft' | 'navy';
    contained?: boolean;
    as?: ElementType;
    label?: string;
    id?: string;
    className?: string;
};

const PADDING = {
    default: 'py-12 md:py-16 lg:py-20',
    large: 'py-12 md:py-20 lg:py-28',
} as const;

const SURFACE = {
    default: 'bg-background text-foreground',
    soft: 'bg-canvas-soft text-foreground',
    navy: 'bg-navy text-white',
} as const;

export function PublicSection({
    children,
    size = 'default',
    surface = 'default',
    contained = true,
    as: Tag = 'section',
    label,
    id,
    className,
}: PublicSectionProps) {
    return (
        <Tag
            id={id}
            aria-label={label}
            className={cn(PADDING[size], SURFACE[surface], className)}
        >
            {contained ? (
                <PublicContainer>{children}</PublicContainer>
            ) : (
                children
            )}
        </Tag>
    );
}

export default PublicSection;
