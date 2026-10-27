import { Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { Text } from '@/components/text';
import { useNavActive } from '@/layouts/public/hooks/use-nav-active';
import { DRAWER_ENTRIES, type NavItem } from '@/layouts/public/navigation';

type DrawerNavProps = {
    onNavigate?: () => void;
};

export function DrawerNav({ onNavigate }: DrawerNavProps) {
    const { isActive } = useNavActive();
    const [expandedMenus, setExpandedMenus] = useState<string[]>([]);

    const toggleMenu = (label: string) => {
        setExpandedMenus((current) =>
            current.includes(label)
                ? current.filter((item) => item !== label)
                : [...current, label],
        );
    };

    const renderLink = (
        entry: (typeof DRAWER_ENTRIES)[number] & { kind: 'link' },
        nested = false,
    ) => {
        const active = isActive(entry.entry);

        return (
            <Link
                key={entry.entry.label}
                href={entry.entry.href}
                onClick={onNavigate}
                aria-current={active ? 'page' : undefined}
                className={cn(
                    'flex min-h-11 items-center rounded-lg px-3 transition-colors',
                    'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                    nested && 'ml-4 border-l border-border pl-4',
                    active
                        ? 'bg-primary/10 font-medium text-primary'
                        : 'text-foreground hover:bg-muted',
                )}
            >
                <Text
                    as="span"
                    variant="nav"
                    weight="medium"
                    color={active ? 'danger' : undefined}
                >
                    {entry.entry.label}
                </Text>
            </Link>
        );
    };

    const renderGroup = (
        label: string,
        children: ((typeof DRAWER_ENTRIES)[number] & {
            kind: 'group';
        })['children'],
    ) => {
        const active = children.some(isActive);
        const expanded = expandedMenus.includes(label) || active;

        return (
            <li key={label} className="space-y-1">
                <button
                    type="button"
                    aria-expanded={expanded}
                    onClick={() => toggleMenu(label)}
                    className={cn(
                        'flex min-h-11 w-full items-center justify-between rounded-lg px-3 text-left transition-colors',
                        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
                        active
                            ? 'bg-primary/10 font-medium text-primary'
                            : 'text-foreground hover:bg-muted',
                    )}
                >
                    <Text
                        as="span"
                        variant="nav"
                        weight="medium"
                        color={active ? 'danger' : undefined}
                    >
                        {label}
                    </Text>

                    <ChevronDown
                        aria-hidden="true"
                        className={cn(
                            'size-4 shrink-0 transition-transform duration-200',
                            expanded && 'rotate-180',
                        )}
                    />
                </button>

                {expanded && (
                    <ul className="space-y-1">
                        {children.map((child) => (
                            <li key={child.label}>
                                {renderLink(
                                    { kind: 'link', entry: child },
                                    true,
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </li>
        );
    };

    return (
        <ul className="flex flex-col gap-1">
            {DRAWER_ENTRIES.map((item: NavItem) =>
                item.kind === 'link' ? (
                    <li key={item.entry.label}>{renderLink(item)}</li>
                ) : (
                    renderGroup(item.label, item.children)
                ),
            )}
        </ul>
    );
}
