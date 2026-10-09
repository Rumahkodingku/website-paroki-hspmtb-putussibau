import { type ReactNode } from 'react';
import { ParishFooter } from '@/layouts/public/components/parish-footer';
import { ParishNavbar } from '@/layouts/public/components/parish-navbar';

export default function PublicLayout({ children }: { children: ReactNode }) {
    return (
        <div className="flex min-h-screen w-full flex-col bg-background">
            <a
                href="#main"
                className="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-50 focus:rounded-md focus:bg-primary focus:px-4 focus:py-2 focus:text-body focus:text-primary-foreground"
            >
                Lewati ke konten utama
            </a>

            <ParishNavbar />

            <main id="main" className="flex-1">
                {children}
            </main>

            <ParishFooter />
        </div>
    );
}
