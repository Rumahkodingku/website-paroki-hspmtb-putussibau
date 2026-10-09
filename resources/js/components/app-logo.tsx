import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';
import { Text } from '@/components/text';

export default function AppLogo() {
    const { name } = usePage().props;

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
                {/*
                 * No dark: variant on the glyph. sidebar-primary is the brand red in
                 * both themes since D-32 - it used to be primary-on-dark in dark mode,
                 * which is why a dark-mode override for black text existed. A white
                 * mark on red is correct in either theme, and it measures 7.65:1.
                 */}
                <AppLogoIcon className="size-5 fill-current text-white" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                {/*
                    leading-tight stays on the wrapper rather than becoming a
                    leading override here: it is what makes this a single-line
                    block in a 32px row, and the paragraph's own line height is
                    not the thing being tuned.
                */}
                <Text
                    as="span"
                    variant="body-strong"
                    weight="semibold"
                    leading="tight"
                    truncate
                >
                    {name}
                </Text>
            </div>
        </>
    );
}
