import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/hooks/use-appearance';

/**
 * The public site's light/dark switch.
 *
 * Three decisions are worth stating, because each of them was available and each
 * was rejected for a reason.
 *
 * It is a two-state control, not the three-way Light/Dark/System selector the
 * admin account page offers. "System" on a public navigation bar would be a
 * preference most visitors do not know they hold, and it would leave the button
 * showing a state the visitor never chose. Someone whose operating system is
 * dark and who wants light is exactly the visitor a bare system-following site
 * loses. Toggling pins the choice explicitly.
 *
 * The accessible name is stable ("Mode gelap") and aria-pressed carries the
 * state. The other shape - a label that changes with the state, saying "Aktifkan
 * mode gelap" then "Aktifkan mode terang" - reads as a different control each
 * time and gives a screen reader user no way to ask what is currently on without
 * activating it. A toggle button that says what it toggles and reports whether
 * it is on says both.
 *
 * Icon size and geometry come from the Button's `size="icon"`, which is
 * size-11 - the 44x44 minimum DESIGN.md states and PRD NFR-RESP repeats. The
 * icons are decorative; the button's own label is the whole accessible name.
 *
 * On switching: nothing here measures or animates. The theme is a class on <html>
 * and the icon swaps, so there is nothing that reflows - which is what DESIGN.md
 * means by "theme switching MUST NOT cause layout shifts". The one thing that
 * could shift is the button itself, and it cannot: both icons are the same size
 * inside a fixed box.
 */
export function AppearanceToggle({ className }: { className?: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const isDark = resolvedAppearance === 'dark';

    return (
        <Button
            type="button"
            variant="outline"
            size="icon"
            className={className}
            aria-label="Mode gelap"
            aria-pressed={isDark}
            onClick={() => updateAppearance(isDark ? 'light' : 'dark')}
        >
            {isDark ? <Moon /> : <Sun />}
        </Button>
    );
}

export default AppearanceToggle;
