import { Head, Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import { index as areaIndex, show as areaShow } from '@/routes/area';
import type { PublicAreaDetail } from '../types';

type Props = {
    area: PublicAreaDetail;
};

export default function StasiLingkunganShow({ area }: Props) {
    const hasChildren = area.children.length > 0;

    return (
        <>
            <Seo
                title={area.name}
                description={area.description ?? undefined}
            />

            <Head title={area.name} />

            <PublicSection>
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Area', href: areaIndex() },
                        { title: area.name },
                    ]}
                />

                <header className="mt-8 space-y-3">
                    <Badge variant="secondary">{area.type_label}</Badge>
                    <Text as="h1" variant="display-lg">
                        {area.name}
                    </Text>
                    {area.description ? (
                        <Text
                            as="p"
                            variant="lead"
                            color="muted"
                            className="max-w-3xl whitespace-pre-line"
                        >
                            {area.description}
                        </Text>
                    ) : null}
                </header>

                {area.parent ? (
                    <Text
                        as="p"
                        variant="caption"
                        color="muted"
                        className="mt-4"
                    >
                        {area.parent.type_label}:{' '}
                        <Link
                            href={areaShow(area.parent.slug)}
                            className="text-primary hover:underline"
                        >
                            {area.parent.name}
                        </Link>
                    </Text>
                ) : null}

                <div className="mt-10 grid gap-6 md:grid-cols-2">
                    {area.leader_name ? (
                        <div className="rounded-lg border border-hairline p-5">
                            <Text as="p" variant="caption" color="muted">
                                Ketua
                            </Text>
                            <Text as="p" variant="body-strong">
                                {area.leader_name}
                            </Text>
                        </div>
                    ) : null}

                    {area.contact_phone ? (
                        <div className="rounded-lg border border-hairline p-5">
                            <Text as="p" variant="caption" color="muted">
                                Kontak
                            </Text>
                            <Text as="p" variant="body-strong">
                                {area.contact_phone}
                            </Text>
                        </div>
                    ) : null}
                </div>

                {/*
                    Where the area is (D-61). The whole block disappears when
                    there is neither an address nor a map link - a greyed-out Maps
                    button on a parish page reads as a bug, not as an absence,
                    which is the reason LocationInfo behaves the same way.
                */}
                {area.address || area.mapsUrl ? (
                    <div className="mt-6 rounded-lg border border-hairline p-5">
                        <Text as="p" variant="caption" color="muted">
                            Alamat
                        </Text>

                        {area.address ? (
                            <Text
                                as="p"
                                variant="body-strong"
                                className="whitespace-pre-line"
                            >
                                {area.address}
                            </Text>
                        ) : null}

                        {area.mapsUrl ? (
                            <a
                                href={area.mapsUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                data-test="area-maps-link"
                                className="mt-2 inline-flex items-center rounded-sm text-sm text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                            >
                                Buka di Google Maps
                            </a>
                        ) : null}
                    </div>
                ) : null}
            </PublicSection>

            {hasChildren ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Daftar Lingkungan" />
                    <ul className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {area.children.map((child) => (
                            <li
                                key={child.id}
                                className="rounded-lg border border-hairline bg-card p-5"
                            >
                                <Link
                                    href={areaShow(child.slug)}
                                    className="block focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                                >
                                    <Text as="p" variant="body-strong">
                                        {child.name}
                                    </Text>
                                </Link>
                            </li>
                        ))}
                    </ul>
                </PublicSection>
            ) : null}

            <PublicSection>
                <Button asChild variant="ghost">
                    <Link href={areaIndex()}>
                        <ArrowLeft className="size-4" aria-hidden="true" />
                        Kembali ke daftar area
                    </Link>
                </Button>
            </PublicSection>
        </>
    );
}
