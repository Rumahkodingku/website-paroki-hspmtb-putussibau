import type { InertiaLinkProps } from '@inertiajs/react';
import { Head, Link } from '@inertiajs/react';
import {
    ArrowRight,
    Church,
    History,
    MapPin,
    UserCog,
    Users,
    type LucideIcon,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { ParishCard, ParishCardContent } from '@/components/parish-card';
import PublicHeroBanner from '@/components/public-hero-banner';
import { PublicSection } from '@/components/public-section';
import { RichText } from '@/components/rich-text';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { home } from '@/routes';
import { pastor, sejarah, struktur, wilayah } from '@/routes/profil';
import type { PublicParishProfile } from '../types';

type Props = {
    profile: PublicParishProfile;
};

type SectionLink = {
    label: string;
    description: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon: LucideIcon;
};

const SECTION_LINKS: SectionLink[] = [
    {
        label: 'Sejarah',
        description: 'Narasi dan linimasa perjalanan paroki',
        href: sejarah(),
        icon: History,
    },
    {
        label: 'Wilayah Pelayanan',
        description: 'Cakupan wilayah, stasi, dan lingkungan',
        href: wilayah(),
        icon: MapPin,
    },
    {
        label: 'Pastor Paroki',
        description: 'Pastor yang bertugas dan riwayatnya',
        href: pastor(),
        icon: UserCog,
    },
    {
        label: 'Struktur Kepengurusan',
        description: 'Susunan pengurus paroki per periode',
        href: struktur(),
        icon: Users,
    },
];

export default function Profil({ profile }: Props) {
    const hasDescription = Boolean(profile.description?.trim());
    const hasCover = Boolean(profile.cover_url);
    const hasEstablishedYear = profile.established_year !== null;
    const hasVideo = Boolean(profile.video_embed_url);
    const hasIntro =
        hasCover || hasDescription || hasEstablishedYear || hasVideo;

    return (
        <>
            <Seo
                title="Profil Paroki"
                description="Mengenal Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau: sejarah, wilayah pelayanan, pastor, dan struktur kepengurusan."
            />
            <Head title="Profil Paroki" />

            <main>
                <PublicHeroBanner
                    eyebrow="PROFIL PAROKI"
                    title="Profil Paroki"
                    description="Mengenal perjalanan iman umat dan arah pelayanan Paroki HSPMTB Putussibau."
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Profil Paroki' },
                    ]}
                />

                {hasCover && (
                    <PublicSection
                        surface="default"
                        label="Foto sampul paroki"
                        className="relative z-10 -mt-10 pt-0 pb-8 sm:-mt-14 sm:pb-10"
                    >
                        <figure className="overflow-hidden rounded-2xl border-4 border-background bg-canvas-subtle shadow-md ring-1 ring-border/50 sm:rounded-3xl">
                            <div className="aspect-16/8 sm:aspect-16/7 lg:aspect-16/6">
                                <img
                                    src={profile.cover_url!}
                                    alt={profile.cover_alt ?? ''}
                                    fetchPriority="high"
                                    className="size-full object-cover object-center"
                                />
                            </div>
                        </figure>
                    </PublicSection>
                )}

                {hasIntro && (
                    <PublicSection
                        surface="default"
                        label="Tentang paroki"
                        className={hasCover ? 'pt-4 sm:pt-6' : undefined}
                    >
                        <div className="grid items-start gap-8 lg:grid-cols-[minmax(0,1.65fr)_minmax(17rem,0.85fr)] lg:gap-12">
                            <div className="min-w-0 space-y-5">
                                <SectionHeading
                                    eyebrow="Sejarah Singkat"
                                    title="Perjalanan Iman dan Pelayanan"
                                />

                                {hasDescription && (
                                    <RichText
                                        html={profile.description!}
                                        className="max-w-3xl"
                                    />
                                )}

                                <Button asChild className="text-sm">
                                    <Link href={sejarah()}>
                                        Baca Sejarah Lengkap
                                        <ArrowRight aria-hidden="true" />
                                    </Link>
                                </Button>
                            </div>

                            {hasEstablishedYear && (
                                <ParishCard className="relative isolate min-h-72 overflow-hidden rounded-2xl border-primary/10 bg-primary/5 py-0 shadow-none">
                                    <div
                                        aria-hidden="true"
                                        className="pointer-events-none absolute -right-5 -bottom-7 z-0 text-primary opacity-[0.08]"
                                    >
                                        <Church className="size-48 stroke-[0.8]" />
                                    </div>
                                    <ParishCardContent className="relative z-10 flex h-full flex-col p-6 sm:p-7">
                                        <div className="flex items-center gap-4">
                                            <span className="flex size-14 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary-on-surface">
                                                <Church
                                                    aria-hidden="true"
                                                    className="size-6"
                                                />
                                            </span>
                                            <div>
                                                <Text
                                                    as="p"
                                                    variant="label"
                                                    className="font-semibold tracking-widest text-primary uppercase"
                                                >
                                                    Tahun Berdiri
                                                </Text>
                                                <Text
                                                    as="p"
                                                    variant="body-sm"
                                                    color="muted"
                                                >
                                                    Gereja Pusat
                                                </Text>
                                            </div>
                                        </div>

                                        <Text
                                            as="p"
                                            variant="display-xl"
                                            className="mt-5 text-center sm:text-left"
                                        >
                                            {profile.established_year}
                                        </Text>

                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                            className="mt-4 leading-relaxed"
                                        >
                                            Gereja pusat paroki berdiri di
                                            tengah Kota Putussibau pada tahun
                                            yang tercantum di atas.
                                        </Text>
                                    </ParishCardContent>
                                </ParishCard>
                            )}
                        </div>
                    </PublicSection>
                )}

                {hasVideo && (
                    <PublicSection surface="soft" label="Video dokumentasi">
                        <div className="grid items-center gap-8 lg:grid-cols-[minmax(0,0.9fr)_minmax(0,1.1fr)] lg:gap-12">
                            <SectionHeading
                                eyebrow="Video Dokumentasi"
                                title="Proses Pembangunan Gereja"
                                description="Saksikan dokumentasi pembangunan gereja sebagai bagian dari perjalanan pelayanan umat di wilayah paroki."
                            />

                            <div className="aspect-video w-full overflow-hidden rounded-2xl border border-border/70 bg-background shadow-sm">
                                <iframe
                                    src={profile.video_embed_url!}
                                    title="Video dokumentasi pembangunan gereja"
                                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                    allowFullScreen
                                    loading="lazy"
                                    referrerPolicy="strict-origin-when-cross-origin"
                                    className="size-full"
                                />
                            </div>
                        </div>
                    </PublicSection>
                )}

                <PublicSection label="Bagian profil paroki">
                    <div className="space-y-8">
                        <SectionHeading
                            eyebrow="Jelajahi Profil"
                            title="Kenali Paroki Lebih Dekat"
                            description="Temukan informasi lengkap mengenai paroki, wilayah pelayanan, pastor, dan struktur kepengurusan."
                        />

                        <nav aria-label="Bagian profil paroki">
                            <ul className="grid gap-3 sm:grid-cols-2">
                                {SECTION_LINKS.map((link) => {
                                    const Icon = link.icon;

                                    return (
                                        <li
                                            key={link.label}
                                            className="min-w-0"
                                        >
                                            <ParishCard className="h-full border-none bg-transparent py-0">
                                                <ParishCardContent className="h-full p-0">
                                                    <Link
                                                        href={link.href}
                                                        className="group flex h-full min-h-28 items-center gap-3 rounded-xl p-4 focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none focus-visible:ring-inset sm:p-5"
                                                    >
                                                        <span className="flex size-11 shrink-0 items-center justify-center rounded-full bg-primary/10 text-primary-on-surface transition-colors group-hover:bg-primary group-hover:text-primary-foreground">
                                                            <Icon
                                                                aria-hidden="true"
                                                                className="size-5"
                                                                strokeWidth={
                                                                    1.8
                                                                }
                                                            />
                                                        </span>
                                                        <span className="min-w-0 flex-1">
                                                            <Text
                                                                as="span"
                                                                variant="heading-sm"
                                                                className="block font-semibold transition-colors group-hover:text-primary"
                                                            >
                                                                {link.label}
                                                            </Text>
                                                            <Text
                                                                as="span"
                                                                variant="body-sm"
                                                                color="muted"
                                                                className="mt-1 block leading-relaxed"
                                                            >
                                                                {
                                                                    link.description
                                                                }
                                                            </Text>
                                                        </span>
                                                        <ArrowRight
                                                            aria-hidden="true"
                                                            className="size-4 shrink-0 text-muted-foreground/70 transition-all group-hover:translate-x-1 group-hover:text-primary motion-reduce:transform-none motion-reduce:transition-none"
                                                        />
                                                    </Link>
                                                </ParishCardContent>
                                            </ParishCard>
                                        </li>
                                    );
                                })}
                            </ul>
                        </nav>
                    </div>
                </PublicSection>
            </main>
        </>
    );
}
