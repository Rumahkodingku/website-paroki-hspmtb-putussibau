import { Link } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Text } from '@/components/text';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSimpleLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col items-center justify-center gap-6 bg-background p-6 md:p-10">
            <div className="w-full max-w-sm">
                <div className="flex flex-col gap-8">
                    <div className="flex flex-col items-center gap-4">
                        <Link
                            href={home()}
                            className="flex flex-col items-center gap-2 font-medium"
                        >
                            <div className="mb-1 flex h-9 w-9 items-center justify-center rounded-md">
                                <AppLogoIcon className="size-9 fill-current text-[var(--foreground)] dark:text-white" />
                            </div>
                            <span className="sr-only">{title}</span>
                        </Link>

                        <div className="space-y-2 text-center">
                            {/*
                                `as="h1"` with the h3 variant rather than
                                variant="h1". An auth page title is the one h1
                                on the page but it is not an editorial heading,
                                and DESIGN.md's display steps would make a login
                                form as loud as a page title. h3 is the smallest
                                heading step and reads at about the size this
                                used to.
                            */}
                            <Text as="h1" variant="h3" weight="medium">
                                {title}
                            </Text>
                            <Text
                                as="p"
                                variant="caption"
                                color="muted"
                                className="text-center"
                            >
                                {description}
                            </Text>
                        </div>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
