import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import PublicLayout from '@/layouts/public-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            // Guest entry point. Wrapped in PublicLayout so the parish navbar
            // and footer have a real page to render against.
            case name === 'welcome':
                return PublicLayout;
            // Authentication pages live under public/auth (see docs/DECISIONS.md
            // D-08) but are rendered by Fortify, not by our routes.
            case name.startsWith('public/auth/'):
                return AuthLayout;
            case name.startsWith('admin/akun/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        // ink-muted-80 from DESIGN.md; the default was an unnamed neutral.
        color: '#475467',
    },
});

// This will set light / dark mode on load...
initializeTheme();
