import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';

export function AppShell({ children }: { children: ReactNode }) {
    const isOpen = usePage().props.sidebarOpen;

    return (
        <div className="bg-canvas-subtle">
            <SidebarProvider defaultOpen={isOpen}>{children}</SidebarProvider>
        </div>
    );
}
