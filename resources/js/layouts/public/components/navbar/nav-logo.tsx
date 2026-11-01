import { cn } from '@/lib/utils';
import { Text } from '@/components/text';
import ParishLogo from '@/components/parish-logo';

type NavLogoProps = {
    transparent: boolean;
    compact?: boolean;
};

export function NavLogo({ transparent, compact = false }: NavLogoProps) {
    const logoWidth = compact ? 32 : 40;
    const logoHeight = compact ? 34 : 42;

    return (
        <span className="flex items-center gap-2 md:gap-3">
            <ParishLogo width={logoWidth} height={logoHeight} />

            <span className="flex flex-col leading-tight">
                <Text
                    as="span"
                    variant="label"
                    color="foreground"
                    weight="semibold"
                    className={cn(
                        'transition-colors duration-300',
                        transparent && 'text-white!',
                    )}
                >
                    HSPMTB Putussibau
                </Text>

                <Text
                    as="span"
                    variant="fine-print"
                    color="muted"
                    weight="medium"
                    className={cn(
                        'transition-colors duration-300',
                        transparent && 'text-white/75!',
                    )}
                >
                    Paroki Hati Santa Perawan Maria Tak Bernoda
                </Text>
            </span>
        </span>
    );
}
