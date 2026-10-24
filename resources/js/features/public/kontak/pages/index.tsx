import { usePage } from '@inertiajs/react';
import {
    ArrowRight,
    Clock3,
    Mail,
    MapPin,
    MessageCircle,
    Navigation,
    Phone,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/empty-state';
import { ParishCard, ParishCardContent } from '@/components/parish-card';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import PublicHeroBanner from '@/components/public-hero-banner';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { cn } from '@/lib/utils';
import type { SharedProps } from '@/types';
import { home } from '@/routes';
import { ContactMap } from '@/components/contact-map';
import { ContactSocial } from '../components/contact-social';
import { CopyButton } from '../components/copy-button';

function ContactMethodCard({
    icon: Icon,
    title,
    description,
    value,
    href,
    action,
    featured = false,
}: {
    icon: LucideIcon;
    title: string;
    description: string;
    value: string;
    href: string;
    action: string;
    featured?: boolean;
}) {
    return (
        <ParishCard
            className={cn('h-full border-none bg-transparent py-0 shadow-none')}
        >
            <ParishCardContent className="flex h-full flex-col gap-4 p-5">
                <div className="flex items-start gap-4">
                    <span
                        className={cn(
                            'flex size-12 shrink-0 items-center justify-center rounded-full',
                            featured
                                ? 'bg-primary text-primary-foreground'
                                : 'bg-primary/10 text-primary-on-surface',
                        )}
                    >
                        <Icon aria-hidden="true" className="size-5" />
                    </span>
                    <div className="min-w-0 flex-1">
                        <Text as="h5" variant="h5" className="font-semibold">
                            {title}
                        </Text>
                        <Text
                            as="p"
                            variant="body-sm"
                            color="muted"
                            className="mt-1.5 leading-relaxed"
                        >
                            {description}
                        </Text>
                    </div>
                </div>

                <Text
                    as="p"
                    variant="body"
                    className="mt-auto text-sm font-medium wrap-break-word"
                    italic
                >
                    {value}
                </Text>

                <Button
                    asChild
                    variant={featured ? 'default' : 'outline'}
                    className="w-full text-sm"
                >
                    <a
                        href={href}
                        target={
                            href.startsWith('https://') ? '_blank' : undefined
                        }
                        rel={
                            href.startsWith('https://')
                                ? 'noopener noreferrer'
                                : undefined
                        }
                    >
                        {action}
                        <ArrowRight aria-hidden="true" />
                    </a>
                </Button>
            </ParishCardContent>
        </ParishCard>
    );
}

export default function Kontak() {
    const { contact } = usePage<SharedProps>().props;

    const hasPhone = Boolean(contact.phone?.display && contact.phone.tel);
    const hasWhatsapp = Boolean(contact.whatsapp?.url);
    const hasEmail = Boolean(contact.email);
    const hasContactMethods = hasPhone || hasWhatsapp || hasEmail;

    const hasAddress = Boolean(contact.address);
    const hasOfficeHours = Boolean(contact.officeHours?.trim());
    const hasLocation = Boolean(contact.mapsEmbedUrl || contact.mapsLink);
    const hasSocial = Object.values(contact.social).some(Boolean);
    const isEmpty =
        !hasContactMethods &&
        !hasAddress &&
        !hasOfficeHours &&
        !hasLocation &&
        !hasSocial;

    return (
        <>
            <Seo
                title="Hubungi Kami"
                description="Alamat, telepon, WhatsApp, surel, jam layanan, dan peta lokasi Paroki HSPMTB Putussibau."
            />

            <PublicHeroBanner
                eyebrow="Kontak Paroki"
                title="Kami siap membantu Anda"
                description="Ada pertanyaan atau membutuhkan informasi pelayanan paroki? Hubungi sekretariat melalui kanal berikut atau kunjungi gereja kami."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Kontak' },
                ]}
                className="bg-canvas-subtle"
                contentClassName="py-12 sm:py-14 lg:py-16"
            />

            {hasContactMethods && (
                <PublicSection>
                    <div className="space-y-8">
                        <SectionHeading
                            title="Hubungi Sekretariat Paroki"
                            description="Pilih kanal komunikasi yang paling nyaman bagi Anda."
                        />

                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {hasWhatsapp && (
                                <ContactMethodCard
                                    icon={MessageCircle}
                                    title="WhatsApp"
                                    description="Untuk pertanyaan umum, informasi pelayanan, dan kebutuhan lainnya."
                                    value={
                                        contact.whatsapp!.display ?? 'WhatsApp'
                                    }
                                    href={contact.whatsapp!.url}
                                    action="Chat WhatsApp"
                                />
                            )}

                            {hasPhone && (
                                <ContactMethodCard
                                    icon={Phone}
                                    title="Telepon"
                                    description="Hubungi langsung sekretariat paroki pada jam pelayanan."
                                    value={contact.phone!.display}
                                    href={contact.phone!.tel}
                                    action="Telepon"
                                />
                            )}

                            {hasEmail && (
                                <ContactMethodCard
                                    icon={Mail}
                                    title="Email"
                                    description="Kirim pesan untuk pertanyaan atau permintaan informasi."
                                    value={contact.email!}
                                    href={`mailto:${contact.email}`}
                                    action="Kirim Email"
                                />
                            )}
                        </div>
                    </div>
                </PublicSection>
            )}

            {(hasAddress || hasOfficeHours || hasLocation) && (
                <PublicSection surface="soft">
                    <div className="grid items-center gap-8 lg:grid-cols-2 lg:gap-12">
                        <div className="space-y-6">
                            <SectionHeading
                                title="Kunjungi Gereja Kami"
                                description="Gereja Katolik Hati Santa Perawan Maria Tak Bernoda Putussibau terbuka untuk umat dan masyarakat."
                            />

                            <div className="mt-8 space-y-5">
                                {hasAddress && (
                                    <div className="flex items-start gap-4">
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary-on-surface">
                                            <MapPin
                                                aria-hidden="true"
                                                className="size-5"
                                            />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <Text as="h5" variant="h5">
                                                Alamat
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                color="muted"
                                                className="mt-1 whitespace-pre-line"
                                            >
                                                {contact.address}
                                            </Text>
                                        </div>
                                        <CopyButton
                                            value={contact.address!}
                                            label="Salin alamat"
                                            className="min-h-11 shrink-0"
                                        />
                                    </div>
                                )}

                                {hasOfficeHours && (
                                    <div className="flex items-start gap-4">
                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary-on-surface">
                                            <Clock3
                                                aria-hidden="true"
                                                className="size-5"
                                            />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <Text as="h5" variant="h5">
                                                Jam Pelayanan Sekretariat
                                            </Text>
                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                color="muted"
                                                className="mt-1 whitespace-pre-line"
                                            >
                                                {contact.officeHours}
                                            </Text>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="min-w-0 space-y-3">
                            {contact.mapsEmbedUrl ? (
                                <div className="overflow-hidden rounded-xl border border-border bg-card shadow-sm [&_iframe]:min-h-72 [&>div]:rounded-none">
                                    <ContactMap
                                        embedUrl={contact.mapsEmbedUrl}
                                        directionsUrl={contact.mapsLink}
                                    />
                                </div>
                            ) : (
                                <ParishCard className="flex min-h-64 items-center justify-center py-8 text-center">
                                    <ParishCardContent>
                                        <MapPin
                                            aria-hidden="true"
                                            className="mx-auto size-8 text-primary-on-surface"
                                        />
                                        <Text
                                            as="p"
                                            variant="h4"
                                            className="mt-3"
                                        >
                                            Peta belum tersedia
                                        </Text>
                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                            className="mt-1"
                                        >
                                            Silakan gunakan alamat gereja di
                                            samping.
                                        </Text>
                                    </ParishCardContent>
                                </ParishCard>
                            )}

                            {contact.mapsLink && (
                                <Button asChild className="w-full text-sm">
                                    <a
                                        href={contact.mapsLink}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <Navigation aria-hidden="true" />
                                        Petunjuk Arah
                                        <ArrowRight aria-hidden="true" />
                                    </a>
                                </Button>
                            )}
                        </div>
                    </div>
                </PublicSection>
            )}

            {hasSocial && (
                <PublicSection>
                    <div className="space-y-8">
                        <SectionHeading
                            title="Terhubung dengan Paroki"
                            description="Ikuti kanal resmi kami untuk mendapatkan informasi dan kegiatan terbaru."
                            align="center"
                        />

                        <div className="mx-auto max-w-5xl">
                            <ContactSocial social={contact.social} />
                        </div>
                    </div>
                </PublicSection>
            )}

            {isEmpty && (
                <PublicSection>
                    <EmptyState
                        level={3}
                        icon={Mail}
                        title="Informasi kontak belum tersedia"
                        description="Pengelola situs belum mengisi informasi kontak, lokasi, atau media sosial paroki."
                    />
                </PublicSection>
            )}
        </>
    );
}
