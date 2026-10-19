import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Phone } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { RichText } from '@/components/rich-text';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as komunitasIndex } from '@/routes/komunitas';
import { CommunityProfileHeader } from '../components/community-profile-header';
import type { PublicCommunityDetail } from '../types';

type Props = {
    community: PublicCommunityDetail;
};

export default function KomunitasShow({ community }: Props) {
    const hasVisionMission = community.vision || community.mission;
    const hasSchedule =
        community.meeting_schedule || community.meeting_location;
    const hasJoining = community.eligibility || community.joining_info;
    const hasContact =
        community.contact &&
        (community.contact.name || community.contact.phone);
    // The server omits `socials` entirely when every column is empty (D-59), so
    // this is a presence check, not a length check.
    const socials = community.socials ?? [];
    const secretariat = community.secretariat;

    return (
        <>
            <Seo
                title={community.name}
                description={community.description ?? undefined}
            />

            <Head title={community.name} />

            <PublicSection>
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Komunitas', href: komunitasIndex() },
                        { title: community.name },
                    ]}
                />

                <CommunityProfileHeader community={community} />
            </PublicSection>

            {hasVisionMission ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Visi dan Misi" />
                    <div className="mt-8 grid gap-8 md:grid-cols-2">
                        {community.vision ? (
                            <div>
                                <Text as="h3" variant="h4">
                                    Visi
                                </Text>
                                {/*
                                    Rich text since D-60, sanitized server-side
                                    in CommunityValidationRules. Rendered as a
                                    paragraph it would print its own markup.
                                */}
                                <RichText
                                    html={community.vision}
                                    className="mt-3"
                                />
                            </div>
                        ) : null}

                        {community.mission ? (
                            <div>
                                <Text as="h3" variant="h4">
                                    Misi
                                </Text>
                                <RichText
                                    html={community.mission}
                                    className="mt-3"
                                />
                            </div>
                        ) : null}
                    </div>
                </PublicSection>
            ) : null}

            {hasSchedule ? (
                <PublicSection>
                    <SectionHeading title="Jadwal dan Lokasi Pertemuan" />
                    <div className="mt-8 grid gap-6 md:grid-cols-2">
                        {community.meeting_schedule ? (
                            <div>
                                <Text as="p" variant="heading-sm">
                                    Jadwal
                                </Text>
                                <Text
                                    as="p"
                                    variant="body"
                                    color="muted"
                                    className="mt-1 whitespace-pre-line"
                                >
                                    {community.meeting_schedule}
                                </Text>
                            </div>
                        ) : null}

                        {community.meeting_location ? (
                            <div>
                                <Text as="p" variant="heading-sm">
                                    Lokasi
                                </Text>
                                <Text
                                    as="p"
                                    variant="body"
                                    color="muted"
                                    className="mt-1"
                                >
                                    {community.meeting_location}
                                </Text>
                            </div>
                        ) : null}
                    </div>
                </PublicSection>
            ) : null}

            {hasJoining ? (
                <PublicSection>
                    <SectionHeading title="Cara Bergabung" />
                    <div className="mt-8 grid gap-8 md:grid-cols-2">
                        {community.eligibility ? (
                            <div>
                                <Text as="p" variant="heading-sm">
                                    Persyaratan
                                </Text>
                                <Text
                                    as="p"
                                    variant="body"
                                    color="muted"
                                    className="mt-1 whitespace-pre-line"
                                >
                                    {community.eligibility}
                                </Text>
                            </div>
                        ) : null}

                        {community.joining_info ? (
                            <div>
                                <Text as="p" variant="heading-sm">
                                    Cara Bergabung
                                </Text>
                                <Text
                                    as="p"
                                    variant="body"
                                    color="muted"
                                    className="mt-1 whitespace-pre-line"
                                >
                                    {community.joining_info}
                                </Text>
                            </div>
                        ) : null}
                    </div>
                </PublicSection>
            ) : null}

            {hasContact ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Kontak Pengurus" />
                    <div className="mt-6 space-y-1">
                        {community.contact?.name ? (
                            <Text as="p" variant="heading-sm">
                                {community.contact.name}
                            </Text>
                        ) : null}
                        {community.contact?.phone ? (
                            <Text as="p" variant="body" color="muted">
                                {community.contact.phone}
                            </Text>
                        ) : null}
                    </div>
                </PublicSection>
            ) : null}

            {socials.length > 0 ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Media Sosial" />
                    <ul className="mt-6 flex flex-wrap gap-3">
                        {socials.map((social) => (
                            <li key={social.label}>
                                <Button asChild variant="outline">
                                    {/*
                                        An external link opens in a new tab and
                                        severs window.opener, both of which
                                        rel="noopener noreferrer" states; the
                                        WhatsApp button above already sets them.
                                    */}
                                    <a
                                        href={social.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        {social.label}
                                    </a>
                                </Button>
                            </li>
                        ))}
                    </ul>
                </PublicSection>
            ) : null}

            <PublicSection>
                <div className="flex flex-col items-start gap-6 rounded-lg border border-border p-6 md:flex-row md:items-center md:justify-between">
                    <div>
                        <Text as="p" variant="heading-sm">
                            Tertarik bergabung?
                        </Text>
                        <Text as="p" variant="body-sm" color="muted">
                            Hubungi pengurus komunitas atau sekretariat paroki
                            untuk informasi lebih lanjut.
                        </Text>
                    </div>

                    {secretariat ? (
                        <div className="flex flex-wrap gap-3">
                            {secretariat.tel ? (
                                <Button asChild>
                                    <a href={`tel:${secretariat.tel}`}>
                                        <Phone
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        Hubungi Sekretariat
                                    </a>
                                </Button>
                            ) : null}

                            {secretariat.whatsapp ? (
                                <Button asChild variant="outline">
                                    <a
                                        href={`https://wa.me/${secretariat.whatsapp}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        WhatsApp
                                    </a>
                                </Button>
                            ) : null}
                        </div>
                    ) : null}
                </div>

                <Button asChild variant="ghost" className="mt-6">
                    <Link href={komunitasIndex()}>
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Kembali ke daftar komunitas
                    </Link>
                </Button>
            </PublicSection>
        </>
    );
}
