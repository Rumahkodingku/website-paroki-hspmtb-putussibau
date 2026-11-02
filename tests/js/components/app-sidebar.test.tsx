import { describe, expect, it } from 'vitest';
import { screen } from '@testing-library/react';
import { renderWithInertia } from '../support/inertia';
import { AppSidebar } from '@/layouts/app/components/app-sidebar';
import { AppShell } from '@/layouts/app/components/app-shell';
import { TooltipProvider } from '@/components/ui/tooltip';

const JADWAL_MISA_ITEMS = [
    ['Misa Rutin', '/admin/jadwal-misa/misa-rutin'],
    ['Misa Khusus', '/admin/jadwal-misa/misa-khusus'],
    ['Pemberitahuan', '/admin/jadwal-misa/pemberitahuan-jadwal'],
] as const;

const PROFIL_PAROKI_ITEMS = [
    ['Informasi Profil', '/admin/profil/informasi'],
    ['Sejarah Paroki', '/admin/profil/sejarah'],
    ['Wilayah Pelayanan', '/admin/profil/wilayah'],
    ['Pastor Paroki', '/admin/profil/pastor'],
    ['Struktur Kepengurusan', '/admin/profil/struktur'],
] as const;

const REFERENCE_ITEMS = [
    ['Lokasi', '/admin/master-data/lokasi'],
    ['Kategori Berita', '/admin/master-data/kategori-berita'],
    ['Kategori Agenda', '/admin/master-data/kategori-agenda'],
    ['Kategori Layanan', '/admin/master-data/kategori-pelayanan'],
    ['Kategori Komunitas', '/admin/master-data/kategori-komunitas'],
    ['Kategori Dokumen', '/admin/master-data/kategori-dokumen'],
] as const;

function renderSidebar(url = '/admin') {
    return renderWithInertia(
        <TooltipProvider delayDuration={0}>
            <AppShell>
                <AppSidebar />
            </AppShell>
        </TooltipProvider>,
        { url },
    );
}

describe('AppSidebar', () => {
    it('renders every sidebar item as a direct link (no dropdown)', () => {
        renderSidebar();

        const flatItems = [
            ...PROFIL_PAROKI_ITEMS,
            ...JADWAL_MISA_ITEMS,
            ['Berita', '/admin/berita/daftar'],
            ['Agenda', '/admin/agenda/daftar'],
            ['Galeri', '/admin/galeri'],
            ['Dokumen', '/admin/dokumen'],
            ['Layanan Paroki', '/admin/pelayanan'],
            ['Komunitas', '/admin/komunitas'],
            ['Area Paroki', '/admin/area'],
            ...REFERENCE_ITEMS,
        ] as const;

        for (const [title, href] of flatItems) {
            expect(screen.getByRole('link', { name: title })).toHaveAttribute(
                'href',
                href,
            );
            expect(screen.queryByRole('button', { name: title })).toBeNull();
        }
    });

    it('does not render the old collapsible module titles as buttons', () => {
        renderSidebar();

        for (const title of [
            'Profil Paroki',
            'Jadwal Misa',
            'Referensi & Kategori',
            'Master Data',
        ]) {
            expect(screen.queryByRole('button', { name: title })).toBeNull();
        }
    });

    it('gives every child its own URL (no duplicates)', () => {
        renderSidebar();

        const hrefs = [
            ...PROFIL_PAROKI_ITEMS,
            ...JADWAL_MISA_ITEMS,
            ...REFERENCE_ITEMS,
        ].map(([title]) =>
            screen.getByRole('link', { name: title }).getAttribute('href'),
        );

        expect(new Set(hrefs).size).toBe(hrefs.length);
    });

    it('does not duplicate any label across the sidebar', () => {
        renderSidebar();

        for (const title of ['Lokasi', 'Kategori Berita', 'Kategori Agenda']) {
            expect(screen.getAllByRole('link', { name: title })).toHaveLength(
                1,
            );
        }
    });
});

describe('AppSidebar — modules with no route yet', () => {
    it('does not link them', () => {
        renderSidebar();

        expect(screen.queryByRole('link', { name: 'Kontak' })).toBeNull();
    });
});
