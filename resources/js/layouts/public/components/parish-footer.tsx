import { Link, usePage } from '@inertiajs/react';
import { Text } from '@/components/text';
import { PublicContainer } from '@/components/public-container';
import type { ContactDetails, IdentityDetails, SeoDefaults } from '@/types';

import {
    MENU_UTAMA,
    PELAYANAN,
    INFORMASI,
    LINK_CLASS,
} from './footer/footer-data';
import { FooterBrand } from './footer/footer-brand';
import { FooterLinks } from './footer/footer-links';
import { FooterContact } from './footer/footer-contact';
import { FooterLegal } from './footer/footer-legal';

export function ParishFooter() {
    const { seo, contact, identity } = usePage<{
        seo?: SeoDefaults;
        contact?: ContactDetails;
        identity?: IdentityDetails;
    }>().props;
    const parishName =
        identity?.name ??
        seo?.siteName ??
        'Paroki Hati Santa Perawan Maria Tak Bernoda';

    return (
        <footer className="relative isolate overflow-hidden border border-border-subtle bg-background shadow-soft-card">
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 -z-10 bg-[url('/hero-section-2.webp')] bg-size-[auto_100%] bg-bottom-right bg-no-repeat opacity-[0.09] dark:opacity-[0.16]"
            />
            <div
                aria-hidden="true"
                className="pointer-events-none absolute inset-0 -z-10 bg-linear-to-r from-background via-background/95 to-background/65 dark:from-background dark:via-background/90 dark:to-background/60"
            />

            <PublicContainer width="default" className="py-8 sm:py-10 lg:py-9">
                <div className="grid gap-x-8 gap-y-8 sm:grid-cols-2 lg:grid-cols-[minmax(230px,1.7fr)_minmax(120px,0.8fr)_minmax(150px,0.9fr)_minmax(150px,0.9fr)_minmax(210px,1.2fr)] lg:gap-x-7">
                    <FooterBrand
                        identity={identity}
                        seo={seo}
                        contact={contact}
                    />

                    <div className="hidden lg:block">
                        <FooterLinks group={MENU_UTAMA} />
                    </div>
                    <div className="hidden lg:block">
                        <FooterLinks group={PELAYANAN} />
                    </div>
                    <div className="hidden lg:block">
                        <FooterLinks group={INFORMASI} />
                    </div>
                    <div className="hidden lg:block">
                        <FooterContact contact={contact} />
                    </div>

                    <div className="hidden grid-cols-2 gap-5 sm:col-span-2 sm:grid lg:hidden">
                        <FooterLinks group={MENU_UTAMA} />
                        <FooterLinks group={PELAYANAN} />
                        <FooterLinks group={INFORMASI} />
                        <FooterContact contact={contact} />
                    </div>

                    <div className="space-y-0 sm:hidden">
                        {[MENU_UTAMA, PELAYANAN, INFORMASI].map((group) => (
                            <details
                                key={group.title}
                                className="group border-t border-border-subtle py-1.5"
                            >
                                <summary className="flex min-h-10 cursor-pointer list-none items-center justify-between gap-3 text-label font-semibold text-foreground [&::-webkit-details-marker]:hidden">
                                    {group.title}
                                    <span
                                        aria-hidden="true"
                                        className="text-muted-foreground transition-transform group-open:rotate-180"
                                    >
                                        ⌄
                                    </span>
                                </summary>
                                <ul className="grid grid-cols-2 gap-x-4 pb-3">
                                    {group.links.map((item) => (
                                        <li key={item.label}>
                                            <Link
                                                href={item.href}
                                                className={LINK_CLASS}
                                            >
                                                {item.label}
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </details>
                        ))}
                    </div>
                </div>

                <div className="mt-8 flex flex-col gap-3 border-t border-border-subtle pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <Text
                        as="p"
                        variant="fine-print"
                        color="subtle"
                        className="max-w-xl"
                    >
                        &copy; {new Date().getFullYear()} {parishName}. Seluruh
                        hak cipta dilindungi.
                    </Text>
                    <FooterLegal />
                </div>
            </PublicContainer>
        </footer>
    );
}

export default ParishFooter;
