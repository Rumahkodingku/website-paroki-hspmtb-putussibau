import { beforeEach, describe, expect, it } from 'vite-plus/test';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { AppearanceToggle } from '@/components/appearance-toggle';
import { initializeTheme } from '@/hooks/use-appearance';
import { renderWithInertia } from '../support/inertia';

/**
 * AppearanceToggle - the public light/dark switch.
 *
 * What is asserted is the promise the button makes to someone who cannot see the
 * icon change: it has one stable name, it reports whether dark mode is on, and
 * pressing it does something and remembers it.
 *
 * The persistence assertions read what useAppearance actually writes rather than
 * re-implementing its logic, because that is the part that has to agree with the
 * cookie the blade template reads on the next request. A toggle that updates
 * localStorage but not the cookie looks correct for the rest of the session and
 * then reverts on the next full page load.
 *
 * useAppearance is a module singleton - one currentAppearance for the module,
 * not one per hook call - so every test starts from a reset. Without that, the
 * order of the cases decides what "the initial state" means: a case that turns
 * dark mode on leaves the next one starting in dark mode, and a single click
 * from there writes "light".
 */
beforeEach(() => {
    localStorage.clear();
    document.cookie = 'appearance=; max-age=0; path=/';
    document.documentElement.classList.remove('dark');
    document.documentElement.style.colorScheme = '';

    // Re-reads storage into the module's current value, which is the same entry
    // point the app calls at start-up.
    initializeTheme();
});
describe('AppearanceToggle', () => {
    it('is a single control with one stable accessible name', () => {
        renderWithInertia(<AppearanceToggle />);

        const toggle = screen.getByRole('button', { name: 'Mode gelap' });

        expect(toggle).toBeInTheDocument();
        // The label must not describe the action. A label that flips between
        // "Aktifkan mode gelap" and "Aktifkan mode terang" gives a screen reader
        // user no way to ask what is currently on without pressing it.
        expect(screen.queryByRole('button', { name: /Aktifkan/ })).toBeNull();
    });

    it('reports the current state through aria-pressed', () => {
        renderWithInertia(<AppearanceToggle />);

        // happy-dom reports prefers-color-scheme as light, so the initial state
        // is dark mode off.
        expect(
            screen.getByRole('button', { name: 'Mode gelap' }),
        ).toHaveAttribute('aria-pressed', 'false');
    });

    /**
     * DESIGN.md and PRD NFR-RESP both put the minimum at 44x44. size-11 is where
     * that comes from; asserted on the class because happy-dom does not resolve
     * Tailwind to pixels, and the real pixel check lives in tests/e2e.
     */
    it('meets the 44px minimum touch target', () => {
        renderWithInertia(<AppearanceToggle />);

        expect(
            screen.getByRole('button', { name: 'Mode gelap' }).className,
        ).toContain('size-11');
    });

    it('turns dark mode on and off', async () => {
        const user = userEvent.setup();

        renderWithInertia(<AppearanceToggle />);

        const toggle = screen.getByRole('button', { name: 'Mode gelap' });

        await user.click(toggle);
        expect(toggle).toHaveAttribute('aria-pressed', 'true');

        await user.click(toggle);
        expect(toggle).toHaveAttribute('aria-pressed', 'false');
    });

    it('applies the dark class to the document element', async () => {
        const user = userEvent.setup();

        renderWithInertia(<AppearanceToggle />);

        expect(document.documentElement).not.toHaveClass('dark');

        await user.click(screen.getByRole('button', { name: 'Mode gelap' }));

        expect(document.documentElement).toHaveClass('dark');
        expect(document.documentElement.style.colorScheme).toBe('dark');
    });

    /**
     * The cookie matters as much as localStorage. HandleAppearance middleware
     * reads it and the blade template uses it to put the dark class on <html>
     * before any JavaScript runs, so without the cookie the choice would survive
     * a client-side navigation and be lost on the next full page load.
     */
    it('persists the choice to both localStorage and the appearance cookie', async () => {
        const user = userEvent.setup();

        renderWithInertia(<AppearanceToggle />);

        await user.click(screen.getByRole('button', { name: 'Mode gelap' }));

        expect(localStorage.getItem('appearance')).toBe('dark');
        expect(document.cookie).toContain('appearance=dark');

        await user.click(screen.getByRole('button', { name: 'Mode gelap' }));

        expect(localStorage.getItem('appearance')).toBe('light');
        expect(document.cookie).toContain('appearance=light');
    });
});
