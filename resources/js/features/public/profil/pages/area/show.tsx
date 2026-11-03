import { Link } from '@inertiajs/react';
import {
    ArrowLeft,
    ArrowUpRight,
    Church,
    Mail,
    MapPin,
    MessageCircle,
    Phone,
    UsersRound,
} from 'lucide-react';

import ParishImage from '@/components/parish-image';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicSection } from '@/components/public-section';
import { Seo } from '@/components/seo';
import { ContactMap } from '@/components/contact-map';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as areaIndex, show as areaShow } from '@/routes/area';

import type { PublicAreaDetail } from '../../types';
import PublicContainer from '@/components/public-container';

type Props = {
    area: PublicAreaDetail;
};

function normalizePhoneNumber(phone: string): string {
    const digits = phone.replace(/\D/g, '');

    if (digits.startsWith('0')) {
        return `62${digits.slice(1)}`;
    }

    if (digits.startsWith('62')) {
        return digits;
    }

    return digits;
}

function formatPhoneHref(phone: string): string {
    return `+${normalizePhoneNumber(phone)}`;
}

export default function Show({ area }: Props) {
    const hasChildren = area.children.length > 0;
    const hasDescription = Boolean(area.description?.trim());
    const hasPhoto = Boolean(area.photoUrl);

    const phone = area.contact_phone?.trim() || '';
    const whatsapp = area.contact_whatsapp?.trim() || '';
    const email = area.contact_email?.trim() || '';

    const hasPhone = Boolean(phone);
    const hasWhatsapp = Boolean(whatsapp);
    const hasEmail = Boolean(email);

    const normalizedWhatsapp = hasWhatsapp
        ? normalizePhoneNumber(whatsapp)
        : '';

    const hasContact =
        Boolean(area.leader_name) || hasPhone || hasWhatsapp || hasEmail;

    const whatsappUrl = hasWhatsapp
        ? `https://wa.me/${normalizedWhatsapp}`
        : null;

    const hasLocation = Boolean(
        area.address || area.mapsUrl || area.mapsEmbedUrl,
    );

    return (
        <>
            <Seo />
            {/* Breadcrumb */}
            <PublicSection>
                <div className="pt-2">
                    <Breadcrumbs
                        withJsonLd
                        breadcrumbs={[
                            { title: 'Beranda', href: home() },
                            { title: 'Profil', href: areaIndex() },
                            { title: 'Area', href: areaIndex() },
                            { title: area.name },
                        ]}
                    />
                </div>
            </PublicSection>

            {/* Community hero */}
            <PublicContainer>
                <section
                    aria-labelledby="area-title"
                    className="relative isolate mt-2 overflow-hidden rounded-2xl border border-border bg-muted"
                >
                    <div className="relative aspect-video min-h-80 w-full sm:aspect-16/7 lg:aspect-16/6 lg:min-h-97.5">
                        {hasPhoto ? (
                            <ParishImage
                                src={area.photoUrl!}
                                alt={`Foto ${area.name}`}
                                width={1280}
                                height={720}
                                aspectRatio="16 / 9"
                                fit="cover"
                                className="absolute inset-0 h-full w-full"
                            />
                        ) : (
                            <div
                                aria-hidden="true"
                                className="absolute inset-0 bg-linear-to-br from-primary/20 via-muted to-primary/5"
                            >
                                <div className="absolute inset-0 flex items-center justify-center text-primary/30">
                                    {area.type === 'stasi' ? (
                                        <Church className="size-24 sm:size-32" />
                                    ) : (
                                        <UsersRound className="size-24 sm:size-32" />
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Contrast overlay for readable text */}
                        <div
                            aria-hidden="true"
                            className="absolute inset-0 bg-linear-to-t from-black/85 via-black/40 to-black/10"
                        />

                        {/* Hero content */}
                        <div className="absolute inset-x-0 bottom-0 z-10 flex flex-col items-start gap-4 p-5 sm:p-8 lg:max-w-3xl lg:p-10">
                            <Badge
                                variant="secondary"
                                className="rounded-full border border-white/20 bg-white/95 px-3 py-1 text-xs font-medium text-primary shadow-sm"
                            >
                                {area.type_label}
                            </Badge>

                            <div className="space-y-3">
                                <Text
                                    as="h1"
                                    variant="display-lg"
                                    id="area-title"
                                    className="max-w-3xl text-3xl leading-tight font-semibold tracking-tight text-white sm:text-4xl lg:text-5xl"
                                >
                                    {area.name}
                                </Text>
                            </div>
                        </div>
                    </div>
                </section>
            </PublicContainer>

            {/* Main profile and contact sidebar */}
            <PublicContainer>
                <div className="grid items-start gap-8 py-8 lg:grid-cols-[minmax(0,1fr)_320px] lg:gap-10 lg:py-10 xl:grid-cols-[minmax(0,1fr)_360px]">
                    {/* Main content */}
                    <div className="min-w-0">
                        <section aria-labelledby="about-area-title">
                            <div className="mb-5 flex items-center gap-3">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                    {area.type === 'stasi' ? (
                                        <Church
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    ) : (
                                        <UsersRound
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    )}
                                </div>

                                <Text
                                    as="h2"
                                    variant="h3"
                                    id="about-area-title"
                                >
                                    Tentang {area.type_label}
                                </Text>
                            </div>

                            {hasDescription ? (
                                <div className="space-y-4">
                                    {area
                                        .description!.trim()
                                        .split(/\n\s*\n/)
                                        .filter((paragraph) => paragraph.trim())
                                        .map((paragraph, index) => (
                                            <Text
                                                key={`${area.id}-paragraph-${index}`}
                                                as="p"
                                                variant="body-sm"
                                                color="muted"
                                                className="leading-7 whitespace-pre-line"
                                            >
                                                {paragraph.trim()}
                                            </Text>
                                        ))}
                                </div>
                            ) : (
                                <p className="text-sm leading-7 text-muted-foreground">
                                    Informasi mengenai {area.name} akan
                                    diperbarui oleh paroki.
                                </p>
                            )}
                        </section>

                        {/* Parent area */}
                        {area.parent ? (
                            <section
                                aria-labelledby="parent-area-title"
                                className="rounded-2xl border border-border p-5 sm:p-6"
                            >
                                <div className="mb-3 flex items-center gap-3">
                                    <div className="flex size-9 items-center justify-center rounded-lg bg-primary/5 text-primary">
                                        <MapPin
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                    </div>

                                    <Text
                                        as="h2"
                                        variant="heading-sm"
                                        id="parent-area-title"
                                    >
                                        Area Induk
                                    </Text>
                                </div>

                                <p className="mb-3 text-sm text-muted-foreground">
                                    {area.parent.type_label}
                                </p>

                                <Link
                                    href={areaShow(area.parent.slug)}
                                    className="group inline-flex min-h-11 items-center gap-2 text-sm font-medium text-primary hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                                >
                                    {area.parent.name}

                                    <ArrowUpRight
                                        className="size-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                        aria-hidden="true"
                                    />
                                </Link>
                            </section>
                        ) : null}

                        {/* Location */}
                        {hasLocation ? (
                            <section aria-labelledby="area-location-title">
                                <div className="mb-4 flex items-center gap-3">
                                    <MapPin
                                        className="size-5 text-primary"
                                        aria-hidden="true"
                                    />

                                    <Text
                                        as="h2"
                                        variant="h3"
                                        id="area-location-title"
                                    >
                                        Lokasi
                                    </Text>
                                </div>

                                {area.address ? (
                                    <p className="mb-4 text-sm leading-6 whitespace-pre-line text-muted-foreground">
                                        {area.address}
                                    </p>
                                ) : null}

                                {area.mapsEmbedUrl || area.mapsUrl ? (
                                    <div className="overflow-hidden rounded-2xl border border-border bg-card [&_iframe]:min-h-72 [&>div]:rounded-none">
                                        <ContactMap
                                            embedUrl={area.mapsEmbedUrl}
                                            directionsUrl={area.mapsUrl}
                                        />
                                    </div>
                                ) : null}

                                {area.mapsUrl ? (
                                    <a
                                        href={area.mapsUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        data-test="area-maps-link"
                                        className="mt-3 inline-flex min-h-11 items-center gap-2 text-sm font-medium text-primary underline-offset-4 hover:underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ring"
                                    >
                                        Buka di Google Maps
                                        <ArrowUpRight
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                    </a>
                                ) : null}
                            </section>
                        ) : null}

                        {/* Related areas */}
                        {hasChildren ? (
                            <section aria-labelledby="related-areas-title">
                                <div className="mb-5 flex items-center gap-3">
                                    <div className="flex size-10 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                        <UsersRound
                                            className="size-5"
                                            aria-hidden="true"
                                        />
                                    </div>

                                    <Text
                                        as="h2"
                                        variant="h3"
                                        id="related-areas-title"
                                    >
                                        Area Terkait
                                    </Text>
                                </div>

                                <ul className="grid gap-3 sm:grid-cols-2">
                                    {area.children.map((child) => (
                                        <li key={child.id}>
                                            <Link
                                                href={areaShow(child.slug)}
                                                className="group flex min-h-20 items-center justify-between gap-3 rounded-xl border border-border p-4 transition-colors hover:border-primary/30 hover:bg-muted/50 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                            >
                                                <div className="min-w-0">
                                                    <p className="mb-1 text-xs text-muted-foreground">
                                                        {child.type_label}
                                                    </p>

                                                    <p className="leading-6 font-medium">
                                                        {child.name}
                                                    </p>
                                                </div>

                                                <ArrowUpRight
                                                    className="size-4 shrink-0 text-muted-foreground transition-colors group-hover:text-primary"
                                                    aria-hidden="true"
                                                />
                                            </Link>
                                        </li>
                                    ))}
                                </ul>
                            </section>
                        ) : null}
                    </div>

                    {/* Contact sidebar */}
                    {hasContact ? (
                        <aside className="min-w-0 lg:sticky">
                            <section
                                aria-labelledby="area-contact-title"
                                className="overflow-hidden"
                            >
                                <div className="border-b border-border px-5 py-4 sm:px-6">
                                    <Text
                                        as="h2"
                                        variant="heading-sm"
                                        id="area-contact-title"
                                    >
                                        Informasi {area.type_label}
                                    </Text>
                                </div>

                                <div className="space-y-0 px-5 sm:px-6">
                                    {/* Leader */}
                                    {area.leader_name ? (
                                        <div className="flex items-center gap-3 border-b border-border/70 py-4">
                                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                                <UsersRound
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            </div>

                                            <div className="min-w-0">
                                                <p className="text-xs text-muted-foreground">
                                                    Ketua {area.type_label}
                                                </p>

                                                <p className="mt-0.5 text-sm font-semibold wrap-break-word">
                                                    {area.leader_name}
                                                </p>
                                            </div>
                                        </div>
                                    ) : null}

                                    {/* Phone */}
                                    {hasPhone ? (
                                        <div className="flex items-center gap-3 border-b border-border/70 py-4">
                                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                                <Phone
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <p className="text-xs text-muted-foreground">
                                                    Kontak
                                                </p>

                                                <a
                                                    href={`tel:${formatPhoneHref(phone)}`}
                                                    className="mt-0.5 inline-block text-sm font-semibold break-all hover:text-primary focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                                >
                                                    {phone}
                                                </a>
                                            </div>
                                        </div>
                                    ) : null}

                                    {/* Email */}
                                    {hasEmail ? (
                                        <div className="mt-4 flex items-center gap-3">
                                            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                                                <Mail
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            </div>

                                            <div className="min-w-0 flex-1">
                                                <p className="text-xs text-muted-foreground">
                                                    Email
                                                </p>

                                                <a
                                                    href={`mailto:${email}`}
                                                    className="mt-0.5 block text-sm font-medium break-all hover:text-primary focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                                >
                                                    {email}
                                                </a>
                                            </div>
                                        </div>
                                    ) : null}

                                    {/* WhatsApp */}
                                    {hasWhatsapp && normalizedWhatsapp ? (
                                        <a
                                            href={whatsappUrl!}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="mt-4 flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-medium text-primary-foreground transition-colors hover:bg-primary/90 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring"
                                        >
                                            <MessageCircle
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                            Hubungi via WhatsApp
                                            <ArrowUpRight
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                        </a>
                                    ) : null}

                                    {/* Phone action when phone exists */}
                                    {hasPhone ? (
                                        <a
                                            href={`tel:${formatPhoneHref(phone)}`}
                                            className={`flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-border px-4 py-3 text-sm font-medium transition-colors hover:bg-muted focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ring ${
                                                hasWhatsapp ? 'mt-2' : 'mt-4'
                                            }`}
                                        >
                                            <Phone
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                            Telepon Sekarang
                                        </a>
                                    ) : null}
                                </div>
                            </section>
                        </aside>
                    ) : null}
                </div>
            </PublicContainer>

            {/* Back navigation */}
            <PublicContainer>
                <div className="border-t border-border/70 py-6">
                    <Button asChild variant="ghost">
                        <Link href={areaIndex()}>
                            <ArrowLeft className="size-4" aria-hidden="true" />
                            Kembali ke daftar area
                        </Link>
                    </Button>
                </div>
            </PublicContainer>
        </>
    );
}
