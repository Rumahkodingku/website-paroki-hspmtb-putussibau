import { Head } from '@inertiajs/react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { RichText } from '@/components/rich-text';
import { Text } from '@/components/text';
import { pastor, sejarah, struktur, wilayah } from '@/routes/profil';
import { ProfileSubNav } from '../components/profile-subnav';
import { ProfileNavCard } from '../components/profile-nav-card';
import type { ProfilSections, PublicParishProfile } from '../types';

type Props = {
    profile: PublicParishProfile | null;
    sections: ProfilSections;
};

const CARD_DESCRIPTIONS: Record<keyof ProfilSections, string> = {
    sejarah: 'Kronologi berdirinya dan perjalanan paroki.',
    wilayah: 'Cakupan wilayah dan stasi pelayanan.',
    pastor: 'Pastor yang bertugas dan riwayat penugasan.',
    struktur: 'Susunan kepengurusan per periode.',
};

export default function ProfilIndex({ profile, sections }: Props) {
    const cards = [
        { key: 'sejarah' as const, title: 'Sejarah Paroki', href: sejarah() },
        {
            key: 'wilayah' as const,
            title: 'Wilayah Pelayanan',
            href: wilayah(),
        },
        { key: 'pastor' as const, title: 'Pastor Paroki', href: pastor() },
        {
            key: 'struktur' as const,
            title: 'Struktur Kepengurusan',
            href: struktur(),
        },
    ].filter((card) => sections[card.key]);

    return (
        <>
            <Seo
                title="Profil Paroki"
                description={
                    profile?.short_description ??
                    'Profil Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau.'
                }
                ogImage={profile?.cover ?? null}
            />

            <Head title="Profil Paroki" />

            <ProfileSubNav />

            <PublicSection>
                <div className="space-y-10">
                    <SectionHeading
                        eyebrow="Profil"
                        title={profile?.name ?? 'Profil Paroki'}
                        description={
                            profile?.short_description ??
                            'Ringkasan paroki akan tampil di sini setelah diisi pengurus.'
                        }
                    />

                    {profile?.cover ? (
                        <img
                            src={profile.cover}
                            alt={profile.cover_alt ?? profile.name ?? ''}
                            className="w-full rounded-xl border border-hairline object-cover"
                        />
                    ) : null}

                    {profile?.description ? (
                        <RichText
                            html={profile.description}
                            className="max-w-3xl"
                        />
                    ) : null}

                    {!profile ? (
                        <Text
                            as="p"
                            variant="body"
                            color="muted"
                            className="rounded-lg border border-dashed border-hairline p-6"
                        >
                            Profil paroki belum dipublikasikan. Silakan kembali
                            lagi nanti.
                        </Text>
                    ) : null}

                    {cards.length > 0 ? (
                        <div className="grid gap-4 sm:grid-cols-2">
                            {cards.map((card) => (
                                <ProfileNavCard
                                    key={card.key}
                                    title={card.title}
                                    description={CARD_DESCRIPTIONS[card.key]}
                                    href={card.href}
                                />
                            ))}
                        </div>
                    ) : null}
                </div>
            </PublicSection>
        </>
    );
}
