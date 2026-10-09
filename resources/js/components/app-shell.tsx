import { usePage } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { SidebarProvider } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    children: ReactNode;
    variant?: AppVariant;
};

/**
 * The admin frame.
 *
 * The bg-canvas-soft wrapper is DESIGN.md v1.1's Admin Surface Hierarchy, and it
 * is here rather than on a token because the admin needs a different canvas from
 * the public site. --background is semantic.background, which the document
 * defines as canvas, and the public pages use it as their page background. The
 * admin wants canvas-soft *behind* white panels, and two different page canvases
 * cannot share one body colour - so the admin supplies its own here and leaves
 * the global token alone.
 *
 * The header variant deliberately does not get it: the same table gives the
 * admin header canvas, not canvas-soft.
 *
 * @see docs/DECISIONS.md D-32
 */
export function AppShell({ children, variant = 'sidebar' }: Props) {
    const isOpen = usePage().props.sidebarOpen;

    if (variant === 'header') {
        return (
            <div className="flex min-h-screen w-full flex-col">{children}</div>
        );
    }

    return (
        <div className="bg-canvas-soft">
            <SidebarProvider defaultOpen={isOpen}>{children}</SidebarProvider>
        </div>
    );
}
