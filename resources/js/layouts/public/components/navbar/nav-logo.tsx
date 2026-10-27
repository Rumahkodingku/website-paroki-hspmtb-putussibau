import { usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Text } from '@/components/text';
import ParishLogo from '@/components/parish-logo';
import type { IdentityDetails } from '@/types';
import {
    primaryIdentityName,
    secondaryIdentityName,
} from '@/layouts/public/navigation';

type NavLogoProps = {
    transparent: boolean;
};

export function NavLogo({ transparent }: NavLogoProps) {
    const { identity } = usePage<{ identity?: IdentityDetails }>().props;
    const primary = primaryIdentityName(identity);
    const secondary = secondaryIdentityName(identity);

    return (
        <span className="flex items-center gap-3">
            <ParishLogo width={40} height={42} />

            <span className="hidden flex-col leading-tight sm:flex">
                <Text
                    as="span"
                    variant="label"
                    color="foreground"
                    weight="medium"
                    className={cn(
                        'transition-colors duration-300',
                        transparent && 'text-white!',
                    )}
                >
                    {primary}
                </Text>

                {secondary ? (
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
                        {secondary}
                    </Text>
                ) : null}
            </span>
        </span>
    );
}
