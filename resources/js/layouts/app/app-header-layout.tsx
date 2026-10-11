import { AppContent } from '@/layouts/app/components/app-content';
import { AppHeader } from '@/layouts/app/components/app-header';
import { AppShell } from '@/layouts/app/components/app-shell';
import type { AppLayoutProps } from '@/types';

export default function AppHeaderLayout({
    children,
    breadcrumbs,
}: AppLayoutProps) {
    return (
        <AppShell variant="header">
            <AppHeader breadcrumbs={breadcrumbs} />
            <AppContent variant="header">{children}</AppContent>
        </AppShell>
    );
}
