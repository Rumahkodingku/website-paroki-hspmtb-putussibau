import type { LucideIcon } from 'lucide-react';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';
import { Text } from '@/components/text';
import type { Appearance } from '@/hooks/use-appearance';
import { useAppearance } from '@/hooks/use-appearance';
import { cn } from '@/lib/utils';

export default function AppearanceToggleTab({
    className = '',
    ...props
}: HTMLAttributes<HTMLDivElement>) {
    const { appearance, updateAppearance } = useAppearance();

    const tabs: { value: Appearance; icon: LucideIcon; label: string }[] = [
        { value: 'light', icon: Sun, label: 'Light' },
        { value: 'dark', icon: Moon, label: 'Dark' },
        { value: 'system', icon: Monitor, label: 'System' },
    ];

    return (
        <div
            className={cn(
                'inline-flex gap-1 rounded-lg bg-muted p-1',
                className,
            )}
            {...props}
        >
            {tabs.map(({ value, icon: Icon, label }) => (
                <button
                    key={value}
                    onClick={() => updateAppearance(value)}
                    className={cn(
                        'flex items-center rounded-md px-3.5 py-1.5 transition-colors',
                        /*
                         * bg-card, not bg-background. DESIGN.md's Appearance
                         * Selector asks for "one elevated surface step" on the
                         * selected option. In light mode card is canvas, so
                         * nothing changes; in dark mode background is
                         * dark-canvas, which sits below the muted container and
                         * would leave the selected tab invisible at 1.11:1.
                         */
                        appearance === value
                            ? 'bg-card text-foreground shadow-xs'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    )}
                >
                    <Icon className="-ml-1 h-4 w-4" />
                    {/*
                        caption, the same 14px this was at as text-sm, now with
                        the design system's own tracking. button-utility is the
                        14px step DESIGN.md documents for buttons, but it is
                        Button's to apply: its line height of 1.29 is measured
                        against a button box, and this label is a span inside a
                        button rather than the button's own type slot.
                    */}
                    <Text as="span" variant="caption" className="ml-1.5">
                        {label}
                    </Text>
                </button>
            ))}
        </div>
    );
}
