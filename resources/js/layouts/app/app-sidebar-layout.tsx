import { AppContent } from '@/layouts/app/components/app-content';
import { AppShell } from '@/layouts/app/components/app-shell';
import { AppSidebar } from '@/layouts/app/components/app-sidebar';
import { AppSidebarHeader } from '@/layouts/app/components/app-sidebar-header';
import type { AppLayoutProps } from '@/types';

export default function AppSidebarLayout({
    children,
    breadcrumbs = [],
}: AppLayoutProps) {
    return (
        <AppShell>
            <AppSidebar />
            <AppContent className="min-w-0 overflow-x-clip">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
