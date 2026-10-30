import { Head, Link } from '@inertiajs/react';
import { ExternalLink } from 'lucide-react';
import { PublicSection } from '@/components/public-section';
import { Seo } from '@/components/seo';
import { RichText } from '@/components/rich-text';
import Image from '@/components/image';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { index as areaIndex } from '@/routes/area';
import type { PublicServiceArea } from '../types';
import PublicHeroBanner from '@/components/public-hero-banner';
import { home } from '@/routes';
import { index as profilIndex } from '@/routes/profil';

type Props = {
    area: PublicServiceArea | null;
};

export default function Wilayah({ area }: Props) {
    return (
        <>
            <Seo
                title="Wilayah Pelayanan"
                description="Cakupan wilayah pelayanan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
            />

            <Head title="Wilayah Pelayanan" />

            <PublicHeroBanner
                eyebrow="PROFIL PAROKI"
                title="Wilayah Pelayanan"
                description="Cakupan wilayah pelayanan Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau."
                breadcrumbs={[
                    { title: 'Beranda', href: home() },
                    { title: 'Profil', href: profilIndex() },
                    { title: 'Wilayah Pelayanan' },
                ]}
            />

            <PublicSection>
                <div className="space-y-10">
                    {area?.content ? (
                        <RichText html={area.content} className="max-w-3xl" />
                    ) : (
                        <Text
                            as="p"
                            variant="body"
                            color="muted"
                            className="rounded-lg border border-dashed border-border p-6"
                        >
                            Informasi wilayah pelayanan belum dipublikasikan.
                        </Text>
                    )}

                    {area?.map ? (
                        <Image
                            src={area.map}
                            alt="Peta wilayah pelayanan paroki"
                            loading="lazy"
                            fit="cover"
                            className="w-full rounded-xl border border-border"
                        />
                    ) : null}

                    {area?.map_url ? (
                        <a
                            href={area.map_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-1 text-primary-on-surface hover:underline"
                        >
                            <Text as="span" variant="label">
                                Buka peta
                            </Text>
                            <ExternalLink
                                className="size-4"
                                aria-hidden="true"
                            />
                        </a>
                    ) : null}

                    <div className="flex flex-col items-start gap-3 border-t border-border pt-6">
                        <Text as="p" variant="body" color="muted">
                            Daftar rinci stasi, wilayah, dan lingkungan beserta
                            pengurusnya tersedia pada halaman berikut.
                        </Text>
                        <Button asChild>
                            <Link href={areaIndex()}>
                                Lihat Area
                                <ExternalLink
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </Link>
                        </Button>
                    </div>
                </div>
            </PublicSection>
        </>
    );
}
