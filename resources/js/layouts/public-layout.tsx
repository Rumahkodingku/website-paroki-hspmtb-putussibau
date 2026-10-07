import { type ReactNode } from 'react';
import { ParishFooter } from '@/components/parish-footer';
import { ParishNavbar } from '@/components/parish-navbar';

/**
 * PublicLayout - DESIGN.md, components.global-nav plus ParishFooter.
 *
 * Phase 01 section 16 asks for Header, Navigation, Main and Footer only, and
 * says not to put real parish data in them. Every value those components render
 * is a [ISI: ...] placeholder, so the shell can be reviewed for structure and
 * spacing without any invented content.
 *
 * The final visual composition belongs to the UI/public website phase. What
 * lives here is the frame: the navbar geometry, the footer information
 * architecture, and a main region that respects the document's section spacing.
 */
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
