import { Moon, Sun } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/hooks/use-appearance';

export function AppearanceToggle({ className }: { className?: string }) {
    const { resolvedAppearance, updateAppearance } = useAppearance();

    const isDark = resolvedAppearance === 'dark';

    return (
        <Button
            type="button"
            variant="default"
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
