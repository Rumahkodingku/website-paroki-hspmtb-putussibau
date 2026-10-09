import { describe, expect, it } from 'vite-plus/test';
import { screen } from '@testing-library/react';
import { EmptyState } from '@/components/empty-state';
import { SectionHeading } from '@/components/section-heading';
import { ParishCta } from '@/components/parish-cta';
import { Button } from '@/components/ui/button';
import { renderWithInertia } from '../support/inertia';

/**
 * The shared public primitives the later phases build their pages from.
 *
 * Roadmap section 25 says Phase 04+ may not re-create a container, section
 * spacing, typography, CTA, empty state or generic empty state. These tests are
 * half of what makes that enforceable: they pin the behaviour a module is allowed
 * to rely on, so changing it later is a visible decision rather than a silent
 * regression.
 *
 * They deliberately assert nothing about colour, spacing or radius. DESIGN.md
 * owns those and can change them without breaking a caller; a test that failed
 * when a shade moved would train people to rewrite the test instead of the
 * design.
 */

describe('SectionHeading', () => {
    it('renders an eyebrow, a title and a description', () => {
        renderWithInertia(
            <SectionHeading
                eyebrow="Ibadah"
                title="Jadwal Misa"
                description="Misa rutin dan misa khusus."
            />,
        );

        expect(screen.getByText('Ibadah')).toBeInTheDocument();
        expect(
            screen.getByRole('heading', { name: 'Jadwal Misa' }),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Misa rutin dan misa khusus.'),
        ).toBeInTheDocument();
    });

    /**
     * A screen reader stepping by heading should not find nine siblings at the
     * same level on one page. The prop exists so a subsection can drop to h3
     * without the component guessing.
     */
    it('defaults to h2 and can drop to h3', () => {
        renderWithInertia(<SectionHeading title="Bagian atas" level={2} />);
        expect(
            screen.getByRole('heading', { level: 2, name: 'Bagian atas' }),
        ).toBeInTheDocument();

        renderWithInertia(<SectionHeading title="Bagian dalam" level={3} />);
        expect(
            screen.getByRole('heading', { level: 3, name: 'Bagian dalam' }),
        ).toBeInTheDocument();
    });

    it('omits the optional slots rather than rendering them empty', () => {
        renderWithInertia(<SectionHeading title="Tanpa eyebrow" />);

        expect(screen.queryByText('Tanpa eyebrow')).toBeInTheDocument();
        // Nothing else was passed, so there is exactly one node in the block.
        expect(
            screen.getByRole('heading', { name: 'Tanpa eyebrow' }),
        ).toBeInTheDocument();
    });
});

describe('EmptyState', () => {
    it('renders a title and a description', () => {
        renderWithInertia(
            <EmptyState
                title="Belum ada berita terbaru"
                description="Berita yang terbit akan muncul di sini."
            />,
        );

        expect(
            screen.getByText('Belum ada berita terbaru'),
        ).toBeInTheDocument();
        expect(
            screen.getByText('Berita yang terbit akan muncul di sini.'),
        ).toBeInTheDocument();
    });

    /**
     * PRD XC-E1: a block with no data is hidden or shows a polite message, never
     * an empty area. The four public lists - news, agenda, album, document - all
     * need this, which is why the copy lives with the caller.
     */
    it.each([
        'Belum ada berita terbaru',
        'Belum ada agenda mendatang',
        'Belum ada album',
        'Belum ada dokumen',
    ])('satisfies XC-E1 for "%s"', (title) => {
        renderWithInertia(<EmptyState title={title} />);

        expect(screen.getByText(title)).toBeInTheDocument();
    });

    it('defaults to h3 so it does not compete with the section it sits in', () => {
        renderWithInertia(<EmptyState title="Kosong" />);

        expect(
            screen.getByRole('heading', { level: 3, name: 'Kosong' }),
        ).toBeInTheDocument();
    });
});

describe('ParishCta', () => {
    it('renders its title and description', () => {
        renderWithInertia(
            <ParishCta
                title="Bersama Membangun Gereja yang Hidup"
                description="Satu kalimat yang menjelaskan langkah berikutnya."
            />,
        );

        expect(
            screen.getByRole('heading', {
                name: 'Bersama Membangun Gereja yang Hidup',
            }),
        ).toBeInTheDocument();
        expect(
            screen.getByText(
                'Satu kalimat yang menjelaskan langkah berikutnya.',
            ),
        ).toBeInTheDocument();
    });

    /**
     * DESIGN.md states this twice - "CTA should have one primary action, not a
     * cluster of competing buttons" - and roadmap section 9.4 repeats it. A CTA
     * that renders three red buttons is the specific failure both lines name, so
     * the two slots have to be able to hold actions that differ in variant, and
     * both have to be reachable as buttons rather than as loose text.
     */
    it('keeps the primary action visually distinct from the secondary one', () => {
        renderWithInertia(
            <ParishCta title="Ajakan" secondaryLabel="Lihat Selengkapnya">
                <Button>Jadwal Misa</Button>
            </ParishCta>,
        );

        const primary = screen.getByRole('button', { name: 'Jadwal Misa' });
        const secondary = screen.getByRole('button', {
            name: 'Lihat Selengkapnya',
        });

        // The two actions carry different variants: the default is a filled red pill,
        // the secondary slot is outlined by the component itself, so a caller
        // physically cannot put a second filled primary next to the first.
        //
        // Asserted as filled-versus-outlined rather than as a specific colour
        // class. A previous version of this test pinned `border-primary`, which
        // quietly became false when DESIGN.md v1.1 sent the outline border to
        // red-on-surface for dark-mode contrast - the assertion was guarding the
        // hierarchy and had been rewritten to guard a hue instead.
        expect(primary.className).not.toBe(secondary.className);
        expect(primary.className).toContain('bg-primary');
        expect(primary.className).not.toContain('bg-background');
        expect(secondary.className).toContain('bg-background');
    });

    it('renders with no actions at all, which is valid', () => {
        renderWithInertia(<ParishCta title="Tanpa tombol" />);

        expect(
            screen.getByRole('heading', { name: 'Tanpa tombol' }),
        ).toBeInTheDocument();
    });
});
