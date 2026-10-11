import { useState } from 'react';
import { Link, type InertiaLinkProps } from '@inertiajs/react';
import {
    CalendarClock,
    CalendarDays,
    ChevronRight,
    Download,
    Globe,
    Images,
    LayoutGrid,
    ListChecks,
    Mail,
    Settings,
    Sparkles,
    UserCog,
    Users,
    type LucideIcon,
} from 'lucide-react';

import AppLogo from '@/components/app-logo';
import { NavUser } from '@/components/nav-user';
import { Text } from '@/components/text';

import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { edit as editSettings } from '@/routes/settings';
import { dashboard } from '@/routes';
import {
    index as massSchedulesIndex,
    list as massSchedulesList,
} from '@/routes/mass-schedules';

import { index as locationsIndex } from '@/routes/locations';
import { index as specialMassesIndex } from '@/routes/special-masses';
import { index as scheduleNoticesIndex } from '@/routes/schedule-notices';
import { edit as editProfile } from '@/routes/profile';
import { list as postsList } from '@/routes/posts';
import { index as postCategoriesIndex } from '@/routes/post-categories';
import { list as eventsList } from '@/routes/events';
import { index as eventCategoriesIndex } from '@/routes/event-categories';

import { useCurrentUrl } from '@/hooks/use-current-url';

type Entry = {
    title: string;
    href?: InertiaLinkProps['href'];
    icon?: LucideIcon;
    children?: Entry[];
};

const CONTENT_ENTRIES: Entry[] = [
    { title: 'Beranda', icon: Sparkles },
    { title: 'Profil', icon: Users },
    {
        title: 'Jadwal Misa',
        href: massSchedulesIndex(),
        icon: CalendarDays,
        children: [
            {
                title: 'Misa Rutin',
                href: massSchedulesList(),
            },
            {
                title: 'Misa Khusus',
                href: specialMassesIndex(),
            },
            {
                title: 'Pemberitahuan',
                href: scheduleNoticesIndex(),
            },
        ],
    },
    { title: 'Berita', href: postsList(), icon: Globe },
    { title: 'Agenda', href: eventsList(), icon: CalendarClock },
    {
        title: 'Master Data',
        icon: ListChecks,
        children: [
            { title: 'Lokasi', href: locationsIndex() },
            { title: 'Kategori Berita', href: postCategoriesIndex() },
            { title: 'Kategori Agenda', href: eventCategoriesIndex() },
        ],
    },
    { title: 'Pelayanan', icon: Sparkles },
    { title: 'Komunitas', icon: Users },
    { title: 'Galeri', icon: Images },
    { title: 'Kontak', icon: Mail },
    { title: 'Download', icon: Download },
];

const ACCOUNT_ENTRIES: Entry[] = [
    {
        title: 'Pengaturan',
        href: editSettings(),
        icon: Settings,
    },
    {
        title: 'Akun',
        href: editProfile(),
        icon: UserCog,
    },
];

function SidebarEntry({ entry }: { entry: Entry }) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    const children = entry.children;

    const [open, setOpen] = useState(() => {
        if (!children) {
            return false;
        }

        return [entry.href, ...children.map((child) => child.href)].some(
            (href) =>
                href !== undefined && isCurrentOrParentUrl(href as string),
        );
    });

    if (!children) {
        return <SidebarEntryLink entry={entry} />;
    }

    const parentActive = false;

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
                className="w-full"
            >
                {entry.icon && <entry.icon />}

                <span className="truncate">{entry.title}</span>

                <ChevronRight
                    aria-hidden="true"
                    className={`ml-auto size-4 shrink-0 transition-transform duration-200 ${
                        open ? 'rotate-90' : ''
                    }`}
                />
            </SidebarMenuButton>

            {open && (
                <SidebarMenuSub>
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
                                <Link href={child.href!} prefetch>
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

function SidebarEntryLink({ entry }: { entry: Entry }) {
    if (!entry.href) {
        return (
            <SidebarMenuItem>
                <SidebarMenuButton
                    aria-disabled="true"
                    className="cursor-default text-muted-foreground/60"
                >
                    {entry.icon && <entry.icon />}

                    <span className="truncate">{entry.title}</span>

                    <Text
                        as="span"
                        variant="micro-legal"
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
        <SidebarMenuItem>
            <SidebarMenuButton asChild tooltip={{ children: entry.title }}>
                <Link href={entry.href} prefetch>
                    {entry.icon && <entry.icon />}

                    <span className="truncate">{entry.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

function SidebarGroup({ label, entries }: { label: string; entries: Entry[] }) {
    return (
        <div className="px-2 py-1">
            <Text
                as="p"
                variant="micro-legal"
                weight="medium"
                tracking="wider"
                transform="uppercase"
                color="muted"
                className="px-2 pb-1"
            >
                {label}
            </Text>

            <SidebarMenu>
                {entries.map((entry) => (
                    <SidebarEntry key={entry.title} entry={entry} />
                ))}
            </SidebarMenu>
        </div>
    );
}

export function AppSidebar() {
    return (
        <Sidebar
            collapsible="offcanvas"
            variant="inset"
            role="navigation"
            aria-label="Navigasi utama"
        >
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <SidebarGroup
                    label="Dashboard"
                    entries={[
                        {
                            title: 'Dashboard',
                            href: dashboard(),
                            icon: LayoutGrid,
                        },
                    ]}
                />

                <SidebarGroup label="Konten" entries={CONTENT_ENTRIES} />

                <SidebarGroup label="Akun" entries={ACCOUNT_ENTRIES} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
