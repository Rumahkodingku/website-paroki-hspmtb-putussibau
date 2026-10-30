import { Link } from '@inertiajs/react';
import { Text } from '@/components/text';
import ParishLogo from '@/components/parish-logo';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { LayoutGrid } from 'lucide-react';
import { dashboard } from '@/routes';
import {
    ACCOUNT_ENTRIES,
    ADMINISTRATION_ENTRIES,
    LITURGY_ENTRIES,
    MINISTRY_ENTRIES,
    PROFILE_ENTRIES,
    PUBLICATION_ENTRIES,
    REFERENCE_ENTRIES,
} from '@/layouts/app/app-sidebar-nav';
import { SidebarGroup } from '@/layouts/app/components/app-sidebar-entry';

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
                            <Link
                                href={dashboard()}
                                prefetch
                                className="mb-2 flex items-center gap-2"
                            >
                                <span className="flex size-10 shrink-0 items-center justify-center">
                                    <ParishLogo
                                        width={40}
                                        height={40}
                                        className="size-10 shrink-0"
                                    />
                                </span>

                                <Text
                                    as="span"
                                    variant="heading-sm"
                                    weight="semibold"
                                    leading="tight"
                                    truncate
                                >
                                    HSPMTB Putussibau
                                </Text>
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

                <SidebarGroup label="Profil Paroki" entries={PROFILE_ENTRIES} />

                <SidebarGroup
                    label="Liturgi & Jadwal"
                    entries={LITURGY_ENTRIES}
                />

                <SidebarGroup label="Publikasi" entries={PUBLICATION_ENTRIES} />

                <SidebarGroup
                    label="Pelayanan & Komunitas"
                    entries={MINISTRY_ENTRIES}
                />

                <SidebarGroup
                    label="Referensi & Kategori"
                    entries={REFERENCE_ENTRIES}
                />

                <SidebarGroup
                    label="Administrasi"
                    entries={ADMINISTRATION_ENTRIES}
                />

                <SidebarGroup label="Akun" entries={ACCOUNT_ENTRIES} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
