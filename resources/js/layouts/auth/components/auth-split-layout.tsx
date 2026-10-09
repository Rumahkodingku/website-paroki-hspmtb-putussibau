import { Link, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/app-logo-icon';
import { Text } from '@/components/text';
import { home } from '@/routes';
import type { AuthLayoutProps } from '@/types';

export default function AuthSplitLayout({
    children,
    title,
    description,
}: AuthLayoutProps) {
    const { name } = usePage().props;

    return (
        <div className="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div className="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r">
                <div className="absolute inset-0 bg-navy-dark" />
                <Link
                    href={home()}
                    className="relative z-20 flex items-center text-lg font-medium"
                >
                    <AppLogoIcon className="mr-2 size-8 fill-current text-white" />
                    {name}
                </Link>
            </div>
            <div className="w-full lg:p-8">
                <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <Link
                        href={home()}
                        className="relative z-20 flex items-center justify-center lg:hidden"
                    >
                        <AppLogoIcon className="h-10 fill-current text-black sm:h-12" />
                    </Link>
                    <div className="flex flex-col items-start gap-2 text-left sm:items-center sm:text-center">
                        {/* See auth-simple-layout: an auth title is an h1 but
                            not an editorial one, so the style and the element
                            are chosen separately. */}
                        <Text as="h1" variant="h3" weight="medium">
                            {title}
                        </Text>
                        {/*
                            text-balance is kept deliberately. This description
                            is the widest line on a 350px column and is the
                            first thing a visitor reads on a page they did not
                            choose to visit.
                        */}
                        <Text
                            as="p"
                            variant="caption"
                            color="muted"
                            className="text-balance"
                        >
                            {description}
                        </Text>
                    </div>
                    {children}
                </div>
            </div>
        </div>
    );
}
