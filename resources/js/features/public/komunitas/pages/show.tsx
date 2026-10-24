import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    Check,
    Copy,
    Heart,
    LinkIcon,
    MapPin,
    MessageCircle,
    Phone,
    UsersRound,
} from 'lucide-react';
import { useState, type ReactNode } from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { RichText } from '@/components/rich-text';
import { Button } from '@/components/ui/button';
import { CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { home } from '@/routes';
import { index as komunitasIndex } from '@/routes/komunitas';
import { CommunityProfileHeader } from '../components/community-profile-header';
import type { PublicCommunityDetail } from '../types';

type Props = {
    community: PublicCommunityDetail;
};

function toWhatsAppUrl(phone: string): string | null {
    const digits = phone.replace(/\D/g, '');
    return digits ? `https://wa.me/${digits}` : null;
}

function DetailCard({
    icon,
    eyebrow,
    title,
    children,
}: {
    icon: ReactNode;
    eyebrow?: string;
    title: string;
    children: ReactNode;
}) {
    return (
        <div className="h-full gap-3 py-4 shadow-none sm:py-5">
            <CardHeader className="flex-row items-center gap-3 px-4 sm:px-5">
                <span className="flex size-10 shrink-0 items-center justify-center rounded-lg bg-primary/5 text-primary-on-surface sm:size-11">
                    {icon}
                </span>
                <div className="min-w-0 pt-0.5">
                    {eyebrow ? (
                        <Text
                            as="p"
                            variant="label"
                            color="muted"
                            className="text-[10px] font-semibold tracking-wide"
                        >
                            {eyebrow}
                        </Text>
                    ) : null}
                    <CardTitle className="mt-0.5 text-sm leading-snug sm:text-base">
                        {title}
                    </CardTitle>
                </div>
            </CardHeader>
            <CardContent className="px-4 sm:px-5">
                <div className="text-sm leading-relaxed text-muted-foreground">
                    {children}
                </div>
            </CardContent>
        </div>
    );
}

export default function KomunitasShow({ community }: Props) {
    console.log(community);
    const [copied, setCopied] = useState(false);
    const hasVisionMission = Boolean(community.vision || community.mission);
    const hasSchedule = Boolean(
        community.meeting_schedule || community.meeting_location,
    );
    const hasJoining = Boolean(community.eligibility || community.joining_info);
    const hasContact = Boolean(
        community.contact?.name || community.contact?.phone,
    );
    const socials = community.socials ?? [];
    const secretariat = community.secretariat;
    const contactPhone = community.contact?.phone ?? null;
    const contactWhatsApp = contactPhone ? toWhatsAppUrl(contactPhone) : null;
    const secretariatWhatsApp = secretariat?.whatsapp
        ? toWhatsAppUrl(secretariat.whatsapp)
        : null;
    const primaryContactUrl =
        secretariatWhatsApp ??
        contactWhatsApp ??
        (secretariat?.tel ? `tel:${secretariat.tel}` : null) ??
        (contactPhone ? `tel:${contactPhone}` : null);
    const primaryContactIsExternal = Boolean(
        primaryContactUrl?.startsWith('https://'),
    );

    async function copyPhone() {
        if (!contactPhone || !navigator.clipboard) return;

        try {
            await navigator.clipboard.writeText(contactPhone);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 1800);
        } catch {
            setCopied(false);
        }
    }

    return (
        <>
            <Seo
                title={community.name}
                description={community.description ?? undefined}
                ogImage={community.thumbnail_url ?? undefined}
            />
            <Head title={community.name} />

            <PublicSection className="pt-5 pb-5 md:pt-6 md:pb-7 lg:pt-7 lg:pb-8">
                <Breadcrumbs
                    className="mb-4"
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Komunitas', href: komunitasIndex() },
                        { title: community.name },
                    ]}
                />

                <CommunityProfileHeader
                    community={community}
                    actions={
                        <div className="flex w-full flex-col gap-2 sm:flex-row sm:flex-wrap lg:w-auto lg:shrink-0">
                            {primaryContactUrl ? (
                                <Button asChild className="w-full text-sm">
                                    <a
                                        href={primaryContactUrl}
                                        {...(primaryContactIsExternal
                                            ? {
                                                  target: '_blank',
                                                  rel: 'noopener noreferrer',
                                              }
                                            : {})}
                                    >
                                        {primaryContactIsExternal ? (
                                            <MessageCircle aria-hidden="true" />
                                        ) : (
                                            <Phone aria-hidden="true" />
                                        )}
                                        Hubungi Pengurus
                                    </a>
                                </Button>
                            ) : null}
                            <Button
                                type="button"
                                variant="outline"
                                className="w-full text-sm"
                                onClick={async () => {
                                    const shareData = {
                                        title: community.name,
                                        text:
                                            community.description ??
                                            community.name,
                                        url: window.location.href,
                                    };

                                    if (navigator.share) {
                                        try {
                                            await navigator.share(shareData);
                                        } catch {
                                            // User cancelled the native share sheet.
                                        }
                                    } else if (navigator.clipboard) {
                                        try {
                                            await navigator.clipboard.writeText(
                                                window.location.href,
                                            );
                                        } catch {
                                            // Clipboard may be blocked by browser permissions.
                                        }
                                    }
                                }}
                            >
                                <LinkIcon aria-hidden="true" />
                                Bagikan
                            </Button>
                        </div>
                    }
                />
            </PublicSection>

            <PublicSection className="py-5 md:py-7 lg:py-8">
                <div className="grid gap-6 lg:gap-8">
                    <div className="min-w-0 space-y-7 sm:space-y-8">
                        {/* Visi & Misi Section */}
                        {hasVisionMission ? (
                            <section aria-labelledby="tentang-komunitas-heading">
                                <SectionHeading
                                    title="Tentang Komunitas"
                                    level={2}
                                    className="gap-2"
                                />

                                {community.description ? (
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                        className="mt-2 max-w-3xl leading-relaxed"
                                    >
                                        {community.description}
                                    </Text>
                                ) : null}

                                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                    {community.vision ? (
                                        <DetailCard
                                            icon={
                                                <Heart
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            }
                                            title="Visi"
                                        >
                                            <RichText html={community.vision} />
                                        </DetailCard>
                                    ) : null}
                                    {community.mission ? (
                                        <DetailCard
                                            icon={
                                                <Check
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            }
                                            title="Misi"
                                        >
                                            <RichText
                                                html={community.mission}
                                            />
                                        </DetailCard>
                                    ) : null}
                                </div>
                            </section>
                        ) : community.description ? (
                            <section>
                                <SectionHeading
                                    title="Tentang Komunitas"
                                    className="gap-2"
                                />
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="mt-2 leading-relaxed"
                                >
                                    {community.description}
                                </Text>
                            </section>
                        ) : null}

                        {hasSchedule ? (
                            <section>
                                <SectionHeading
                                    title="Informasi Praktis"
                                    className="gap-2"
                                />
                                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                    {community.meeting_schedule ? (
                                        <DetailCard
                                            icon={
                                                <CalendarDays
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            }
                                            title="Jadwal Pertemuan"
                                        >
                                            <p className="whitespace-pre-line">
                                                {community.meeting_schedule}
                                            </p>
                                        </DetailCard>
                                    ) : null}
                                    {community.meeting_location ? (
                                        <DetailCard
                                            icon={
                                                <MapPin
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            }
                                            title="Lokasi Pertemuan"
                                        >
                                            <p>{community.meeting_location}</p>
                                        </DetailCard>
                                    ) : null}
                                </div>
                            </section>
                        ) : null}

                        {hasJoining ? (
                            <section
                                id="cara-bergabung"
                                className="scroll-mt-24"
                            >
                                <SectionHeading
                                    title="Bergabung Bersama Kami"
                                    className="gap-2"
                                />
                                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                    {community.eligibility ? (
                                        <DetailCard
                                            icon={
                                                <UsersRound
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            }
                                            title="Persyaratan"
                                        >
                                            <p className="whitespace-pre-line">
                                                {community.eligibility}
                                            </p>
                                        </DetailCard>
                                    ) : null}
                                    {community.joining_info ? (
                                        <DetailCard
                                            icon={
                                                <Check
                                                    className="size-5"
                                                    aria-hidden="true"
                                                />
                                            }
                                            title="Cara Bergabung"
                                        >
                                            <p className="whitespace-pre-line">
                                                {community.joining_info}
                                            </p>
                                        </DetailCard>
                                    ) : null}
                                </div>
                            </section>
                        ) : null}
                    </div>
                </div>
            </PublicSection>

            {hasContact || secretariat || socials.length > 0 ? (
                <PublicSection className="py-5 md:py-7 lg:py-8">
                    <section id="kontak-komunitas" className="scroll-mt-24">
                        <SectionHeading
                            title="Kontak Pengurus"
                            className="gap-2"
                        />

                        <div className="mt-4 flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                            <div className="flex min-w-0 items-center gap-3">
                                <span className="flex size-10 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary-on-surface">
                                    <UsersRound
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div className="min-w-0">
                                    {community.contact?.name ? (
                                        <Text as="p" variant="heading-sm">
                                            {community.contact.name}
                                        </Text>
                                    ) : (
                                        <Text as="p" variant="heading-sm">
                                            Pengurus komunitas
                                        </Text>
                                    )}
                                    {contactPhone ? (
                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                            className="mt-1 break-all"
                                        >
                                            <Phone
                                                className="mr-1 inline size-3.5"
                                                aria-hidden="true"
                                            />
                                            {contactPhone}
                                        </Text>
                                    ) : null}
                                </div>
                            </div>

                            <div className="flex w-full flex-col gap-2 sm:w-auto sm:flex-row">
                                {contactWhatsApp ? (
                                    <Button
                                        asChild
                                        className="w-full text-sm sm:w-auto"
                                    >
                                        <a
                                            href={contactWhatsApp}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <MessageCircle aria-hidden="true" />
                                            Chat via WhatsApp
                                        </a>
                                    </Button>
                                ) : contactPhone ? (
                                    <Button
                                        asChild
                                        className="w-full text-sm sm:w-auto"
                                    >
                                        <a href={`tel:${contactPhone}`}>
                                            <Phone aria-hidden="true" />
                                            Telepon
                                        </a>
                                    </Button>
                                ) : null}
                                {contactPhone ? (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="w-full text-sm sm:w-auto"
                                        onClick={copyPhone}
                                    >
                                        {copied ? (
                                            <Check aria-hidden="true" />
                                        ) : (
                                            <Copy aria-hidden="true" />
                                        )}
                                        {copied
                                            ? 'Nomor Disalin'
                                            : 'Salin Nomor'}
                                    </Button>
                                ) : null}
                            </div>
                        </div>

                        {socials.length > 0 ? (
                            <div className="mt-4 flex flex-wrap gap-2">
                                {socials.map((social) => (
                                    <Button
                                        key={social.label}
                                        asChild
                                        variant="outline"
                                        size="sm"
                                    >
                                        <a
                                            href={social.url}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            {social.label}
                                        </a>
                                    </Button>
                                ))}
                            </div>
                        ) : null}
                    </section>
                </PublicSection>
            ) : null}

            <PublicSection className="pt-3 pb-8 md:pt-4 md:pb-10">
                <Separator className="mb-5" />
                <Button asChild variant="ghost" className="-ml-3">
                    <Link href={komunitasIndex()}>
                        <ArrowLeft aria-hidden="true" />
                        Kembali ke daftar komunitas
                    </Link>
                </Button>
            </PublicSection>
        </>
    );
}
