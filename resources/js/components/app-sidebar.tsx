import { type InertiaLinkProps, Link } from '@inertiajs/react';
import {
    CalendarDays,
    Download,
    Images,
    LayoutGrid,
    Mail,
    Newspaper,
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
} from '@/components/ui/sidebar';
import { edit as editSettings } from '@/routes/settings';
import { dashboard } from '@/routes';
import { edit as editProfile } from '@/routes/profile';

/*
 * Admin sidebar.
 *
 * Phase 01 section 12 lists the navigation this should carry, and it also warns
 * that a module is not finished merely because its menu entry appears. Only
 * Dashboard, the account pages and Appearance have routes today, so those are
 * links and everything else is a disabled row with a "belum" marker. No entry
 * points at a URL that would 404.
 *
 * The two entries that were here before, Repository and Documentation, linked to
 * laravel.com and the Laravel starter repository. They have been removed.
 */

type Entry = {
    title: string;
    /** Absent until the module behind it exists. */
    href?: InertiaLinkProps['href'];
    icon: LucideIcon;
};

const CONTENT_ENTRIES: Entry[] = [
    { title: 'Beranda', icon: Sparkles },
    { title: 'Profil', icon: Users },
    { title: 'Jadwal Misa', icon: CalendarDays },
    { title: 'Berita', icon: Newspaper },
    { title: 'Agenda', icon: CalendarDays },
    { title: 'Pelayanan', icon: Sparkles },
    { title: 'Komunitas', icon: Users },
    { title: 'Galeri', icon: Images },
    { title: 'Kontak', icon: Mail },
    { title: 'Download', icon: Download },
];

const ACCOUNT_ENTRIES: Entry[] = [
    { title: 'Pengaturan', href: editSettings(), icon: Settings },
    { title: 'Akun', href: editProfile(), icon: UserCog },
];

function SidebarEntry({ entry }: { entry: Entry }) {
    if (!entry.href) {
        return (
            <SidebarMenuItem>
                <SidebarMenuButton
                    aria-disabled="true"
                    className="cursor-default text-muted-foreground/60"
                >
                    <entry.icon />
                    <span className="truncate">{entry.title}</span>
                    {/*
                        tracking-tight overrides the token's own -0.08px. That
                        is deliberate and it is a real trade: the token was
                        measured for running text, and a three-letter status
                        marker sitting at 10px next to a 14px label needs the
                        extra separation to stay legible at that size.
                    */}
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
                    <entry.icon />
                    <span className="truncate">{entry.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

function SidebarGroup({ label, entries }: { label: string; entries: Entry[] }) {
    return (
        <div className="px-2 py-1">
            {/*
                An uppercase group label, which DESIGN.md permits for exactly
                this: "Small uppercase labels may be used for eyebrow text."
                The spacing is tracking-wider rather than the token's own
                negative tracking, because uppercase text reads as a solid
                block at 10px without it. font-medium replaces the token's 400
                for the same reason.
            */}
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
        <Sidebar collapsible="icon" variant="inset">
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
