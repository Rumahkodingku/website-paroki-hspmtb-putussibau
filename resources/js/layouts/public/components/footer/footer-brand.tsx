import { MapPin } from 'lucide-react';
import { ParishLogo } from '@/components/parish-logo';
import { Text } from '@/components/text';
import type { ContactDetails, IdentityDetails, SeoDefaults } from '@/types';

type FooterBrandProps = {
    identity: IdentityDetails | undefined;
    seo: SeoDefaults | undefined;
    contact: ContactDetails | undefined;
};

export function FooterBrand({ identity, seo, contact }: FooterBrandProps) {
    const parishName =
        identity?.name ??
        seo?.siteName ??
        'Paroki Hati Santa Perawan Maria Tak Bernoda';

    return (
        <section
            className="min-w-0 sm:col-span-2 lg:col-span-1"
            aria-label="Identitas paroki"
        >
            <div className="flex items-center gap-3">
                <ParishLogo
                    width={54}
                    height={56}
                    className="size-12 shrink-0 sm:size-14"
                />
                <div className="min-w-0">
                    <Text
                        as="p"
                        variant="label"
                        color="foreground"
                        className="max-w-60 text-sm leading-snug font-semibold"
                    >
                        {parishName}
                    </Text>
                </div>
            </div>

            {identity?.shortDescription ? (
                <Text
                    as="p"
                    variant="fine-print"
                    color="muted"
                    className="mt-4 max-w-sm"
                >
                    {identity.shortDescription}
                </Text>
            ) : (
                <Text
                    as="p"
                    variant="fine-print"
                    color="muted"
                    className="mt-4 max-w-sm"
                >
                    Gereja Katolik Hati Santa Perawan Maria Tak Bernoda,
                    Putussibau, bagian dari Gereja Katolik Roma di Keuskupan
                    Sintang.
                </Text>
            )}

            {contact?.address ? (
                <div className="mt-4 flex items-start gap-2.5">
                    <MapPin
                        className="mt-0.5 size-4 shrink-0 text-foreground"
                        aria-hidden="true"
                    />
                    <div className="min-w-0">
                        <Text
                            as="p"
                            variant="fine-print"
                            color="muted"
                            className="whitespace-pre-line"
                        >
                            {contact.address}
                        </Text>
                        {contact.mapsLink ? (
                            <a
                                href={contact.mapsLink}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="mt-1 inline-flex min-h-8 items-center text-fine-print font-medium text-primary-on-surface hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                Lihat di Google Maps ↗
                            </a>
                        ) : null}
                    </div>
                </div>
            ) : null}
        </section>
    );
}
