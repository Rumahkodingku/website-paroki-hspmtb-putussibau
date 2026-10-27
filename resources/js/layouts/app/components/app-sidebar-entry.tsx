import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';

import { Text } from '@/components/text';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { ChevronRight } from 'lucide-react';

import { useCurrentUrl } from '@/hooks/use-current-url';
import { visibleEntries, type Entry } from '@/layouts/app/app-sidebar-nav';

export function SidebarEntry({ entry }: { entry: Entry }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const children = entry.children;

    const [open, setOpen] = useState(() => {
        if (!children) {
            return false;
        }

        return [entry.href, ...children.map((child) => child.href)].some(
            (href) => href !== undefined && isCurrentOrParentUrl(href),
        );
    });

    if (!children) {
        return <SidebarEntryLink entry={entry} />;
    }

    const parentActive = [
        entry.href,
        ...children.map((child) => child.href),
    ].some((href) => href !== undefined && isCurrentOrParentUrl(href));

    const handleParentClick = () => {
        setOpen((previous) => !previous);
    };

    return (
        <SidebarMenuItem
            data-state={open ? 'open' : 'closed'}
            className="group/menu-collapsible"
        >
            <SidebarMenuButton
                type="button"
                isActive={parentActive}
                onClick={handleParentClick}
                tooltip={{ children: entry.title }}
                aria-expanded={open}
                className="w-full cursor-pointer"
            >
                {entry.icon && <entry.icon />}

                <span className="truncate font-medium">{entry.title}</span>

                <ChevronRight
                    aria-hidden="true"
                    className={`ml-auto size-4 shrink-0 transition-transform duration-200 ${open ? 'rotate-90' : ''}`}
                />
            </SidebarMenuButton>

            {open && (
                <SidebarMenuSub className="font-medium">
                    {children.map((child) => (
                        <SidebarMenuSubItem key={child.title}>
                            <SidebarMenuSubButton
                                asChild
                                isActive={
                                    child.href
                                        ? isCurrentOrParentUrl(
                                              child.href as string,
                                          )
                                        : false
                                }
                            >
                                <Link
                                    href={child.href}
                                    prefetch
                                    className="py-4"
                                >
                                    <span className="truncate">
                                        {child.title}
                                    </span>
                                </Link>
                            </SidebarMenuSubButton>
                        </SidebarMenuSubItem>
                    ))}
                </SidebarMenuSub>
            )}
        </SidebarMenuItem>
    );
}

export function SidebarEntryLink({ entry }: { entry: Entry }) {
    if (!entry.href) {
        return (
            <SidebarMenuItem>
                <SidebarMenuButton
                    aria-disabled="true"
                    className="cursor-default text-muted-foreground/60"
                >
                    {entry.icon && <entry.icon />}

                    <span className="truncate font-medium">{entry.title}</span>

                    <Text
                        as="span"
                        variant="fine-print"
                        tracking="tight"
                        className="ml-auto text-muted-foreground/60"
                    >
                        belum
                    </Text>
                </SidebarMenuButton>
            </SidebarMenuItem>
        );
    }

    return (
        <SidebarMenuItem className="cursor-pointer font-medium">
            <SidebarMenuButton asChild tooltip={{ children: entry.title }}>
                <Link href={entry.href} prefetch>
                    {entry.icon && <entry.icon />}

                    <span className="truncate">{entry.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

export function SidebarGroup({
    label,
    entries,
}: {
    label: string;
    entries: Entry[];
}) {
    const { abilities } = usePage().props;
    const visible = visibleEntries(entries, abilities);

    if (visible.length === 0) {
        return null;
    }

    return (
        <div className="px-2 py-1">
            <Text
                as="p"
                variant="fine-print"
                tracking="wider"
                transform="uppercase"
                className="px-2 pb-1 font-bold"
            >
                {label}
            </Text>

            <SidebarMenu className="ml-2">
                {visible.map((entry) => (
                    <SidebarEntry key={entry.title} entry={entry} />
                ))}
            </SidebarMenu>
        </div>
    );
}
