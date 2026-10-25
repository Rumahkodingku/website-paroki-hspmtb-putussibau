import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Clock,
    ExternalLink,
    Mail,
    MapPin,
    MessageCircle,
    Navigation,
    Phone,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';

import { ContactMap } from '@/components/contact-map';
import { PublicSection } from '@/components/public-section';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { index as kontak } from '@/routes/kontak';
import type { ContactDetails } from '@/types';
import SectionHeading from '@/components/section-heading';

type HomeContactItem = {
    key: string;
    icon: LucideIcon;
    label: string;
    value: string;
    href?: string;
    external?: boolean;
};

function ContactItem({ item }: { item: HomeContactItem }) {
    const Icon = item.icon;

    const action =
        item.key === 'whatsapp'
            ? 'Chat via WhatsApp'
            : item.key === 'email'
              ? 'Kirim Email'
              : null;

    return (
        <li className="flex min-w-0 items-start gap-4">
            <span
                className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/8 text-primary"
                aria-hidden="true"
            >
                <Icon className="size-5" />
            </span>

            <div className="min-w-0 flex-1 space-y-1">
                <Text
                    as="p"
                    variant="label"
                    color="foreground"
                    className="font-semibold"
                >
                    {item.label}
                </Text>

                {item.href ? (
                    <a
                        href={item.href}
                        {...(item.external
                            ? {
                                  target: '_blank',
                                  rel: 'noopener noreferrer',
                              }
                            : {})}
                        className="block w-fit max-w-full rounded-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        <Text
                            as="span"
                            variant="body"
                            color="muted"
                            className="block wrap-break-word transition-colors hover:text-primary"
                        >
                            {item.value}
                        </Text>
                    </a>
                ) : (
                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        className="wrap-break-word"
                    >
                        {item.value}
                    </Text>
                )}

                {action && item.href ? (
                    <a
                        href={
                            item.key === 'email'
                                ? `mailto:${item.value}`
                                : item.href
                        }
                        {...(item.key === 'whatsapp'
                            ? {
                                  target: '_blank',
                                  rel: 'noopener noreferrer',
                              }
                            : {})}
                        className="group mt-2 inline-flex w-fit max-w-full items-center gap-1.5 text-sm font-medium text-primary underline-offset-4 transition-colors hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        {action}
                        <ExternalLink
                            className="size-3.5 shrink-0 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5 motion-reduce:transform-none motion-reduce:transition-none"
                            aria-hidden="true"
                        />
                    </a>
                ) : null}
            </div>
        </li>
    );
}

export function HomeContactSection({ contact }: { contact: ContactDetails }) {
    if (!contact.homeShowContact) {
        return null;
    }

    const items: HomeContactItem[] = [];

    if (contact.address) {
        items.push({
            key: 'address',
            icon: MapPin,
            label: 'Alamat',
            value: contact.address,
        });
    }

    if (contact.phone) {
        items.push({
            key: 'phone',
            icon: Phone,
            label: 'Telepon',
            value: contact.phone.display,
            href: contact.phone.tel,
        });
    }

    if (contact.whatsapp) {
        items.push({
            key: 'whatsapp',
            icon: MessageCircle,
            label: 'WhatsApp',
            value: contact.whatsapp.display ?? 'Chat WhatsApp',
            href: contact.whatsapp.url,
            external: true,
        });
    }

    if (contact.email) {
        items.push({
            key: 'email',
            icon: Mail,
            label: 'Surel',
            value: contact.email,
            href: `mailto:${contact.email}`,
        });
    }

    const hasOfficeHours = Boolean(contact.officeHours);
    const hasMap = Boolean(contact.mapsEmbedUrl || contact.mapsLink);

    if (items.length === 0 && !hasOfficeHours && !hasMap) {
        return null;
    }

    return (
        <PublicSection>
            <div className="space-y-10 lg:space-y-12">
                <SectionHeading
                    eyebrow="Kontak Paroki"
                    title="Hubungi Kami"
                    description="Kami siap membantu kebutuhan informasi dan pelayanan umat melalui berbagai kanal komunikasi."
                    action={
                        <Button
                            asChild
                            variant="outline"
                            className="group h-11 rounded-full border-primary/70 bg-transparent px-5 text-sm text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
                        >
                            <Link href={kontak()}>
                                Halaman Kontak
                                <ArrowRight
                                    aria-hidden="true"
                                    className="size-4 transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                                />
                            </Link>
                        </Button>
                    }
                />

                {/* Contact items */}
                {items.length > 0 ? (
                    <ul className="grid grid-cols-1 gap-x-12 gap-y-8 sm:grid-cols-2 lg:gap-x-16 lg:gap-y-9">
                        {items.map((item) => (
                            <ContactItem key={item.key} item={item} />
                        ))}
                    </ul>
                ) : null}

                {/* Office hours */}
                {hasOfficeHours ? (
                    <div className="border-t border-border pt-6">
                        <div className="flex items-start gap-4">
                            <span
                                className="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/8 text-primary"
                                aria-hidden="true"
                            >
                                <Clock className="size-5" />
                            </span>

                            <div className="min-w-0 space-y-1.5">
                                <Text
                                    as="p"
                                    variant="label"
                                    className="font-semibold text-foreground"
                                >
                                    Jam Layanan
                                </Text>

                                <Text
                                    as="p"
                                    variant="body"
                                    color="muted"
                                    className="wrap-break-word whitespace-pre-line"
                                >
                                    {contact.officeHours}
                                </Text>
                            </div>
                        </div>
                    </div>
                ) : null}

                {/* Church location */}
                {hasMap ? (
                    <section
                        aria-labelledby="home-contact-map-heading"
                        className="space-y-5 rounded-2xl border border-border/70 bg-muted/30 p-4 sm:p-6 lg:p-8"
                    >
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                            <div className="max-w-xl space-y-3">
                                <Text
                                    as="h3"
                                    variant="heading-md"
                                    className="text-foreground"
                                >
                                    Temukan Lokasi Kami
                                </Text>

                                <Text as="p" variant="body" color="muted">
                                    Gunakan peta berikut untuk menemukan lokasi
                                    Gereja Paroki HSPMTB Putussibau.
                                </Text>
                            </div>

                            {contact.mapsLink ? (
                                <Button
                                    asChild
                                    className="group h-11 w-full shrink-0 rounded-full px-5 sm:w-fit"
                                >
                                    <a
                                        href={contact.mapsLink}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Navigation
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        Petunjuk Arah
                                        <ExternalLink
                                            className="size-3.5"
                                            aria-hidden="true"
                                        />
                                    </a>
                                </Button>
                            ) : null}
                        </div>

                        <div className="overflow-hidden rounded-xl border border-border bg-background [&_iframe]:block [&_iframe]:h-75 [&_iframe]:w-full [&_iframe]:border-0 sm:[&_iframe]:h-95 lg:[&_iframe]:h-110">
                            <ContactMap
                                embedUrl={contact.mapsEmbedUrl}
                                directionsUrl={contact.mapsLink}
                            />
                        </div>
                    </section>
                ) : null}
            </div>
        </PublicSection>
    );
}

export default HomeContactSection;
