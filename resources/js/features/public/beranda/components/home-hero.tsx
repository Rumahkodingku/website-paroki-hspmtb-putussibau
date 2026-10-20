import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, Church } from 'lucide-react';
import { useState } from 'react';
import { PublicContainer } from '@/components/public-container';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { sejarah } from '@/routes/profil';
import type { IdentityDetails } from '@/types';
import type { HomeHero as HomeHeroData } from '../types';

export function HomeHero({
    hero,
    identity,
}: {
    hero: HomeHeroData;
    identity: IdentityDetails;
}) {
    const [imageFailed, setImageFailed] = useState(false);

    const title =
        hero.title ??
        identity.name ??
        'Paroki Hati Santa Perawan Maria Tak Bernoda Putussibau';

    const eyebrow =
        identity.tagline ??
        identity.shortName ??
        'Berjalan bersama dalam iman, pengharapan, dan kasih';

    const description = hero.description ?? identity.shortDescription;

    const titleWords = title.trim().split(/\s+/);
    const accentWord =
        titleWords.length > 1 ? titleWords[titleWords.length - 1] : '';

    const titlePrefix =
        titleWords.length > 1 ? titleWords.slice(0, -1).join(' ') : title;

    return (
        <section className="relative isolate overflow-hidden bg-navy text-white">
            {/* Hero background image */}
            {!imageFailed && hero.image && (
                <img
                    src={hero.image}
                    alt={hero.imageAlt}
                    width={hero.width}
                    height={hero.height}
                    loading="eager"
                    fetchPriority="high"
                    decoding="async"
                    onError={() => setImageFailed(true)}
                    className="absolute inset-0 size-full object-cover object-[62%_center] md:object-center"
                />
            )}

            {/* Overall image contrast */}
            <div aria-hidden="true" className="absolute inset-0 bg-black/20" />

            {/* Darker left side for readable text */}
            <div
                aria-hidden="true"
                className="absolute inset-0 bg-linear-to-r from-navy-deep/95 via-navy/75 to-navy/10"
            />

            {/* Subtle bottom shade behind quick links */}
            <div
                aria-hidden="true"
                className="absolute inset-x-0 bottom-0 h-72 bg-linear-to-t from-black/55 to-transparent"
            />

            <PublicContainer className="relative">
                <div className="flex min-h-190 flex-col pt-28 pb-16 sm:min-h-200 sm:pt-32 md:min-h-212.5 md:pt-36 lg:min-h-[min(900px,100svh)]">
                    {/* Hero content */}
                    <div className="flex flex-1 items-center py-10 md:py-12">
                        <div className="w-full max-w-3xl space-y-5 md:space-y-6">
                            {eyebrow && (
                                <div className="flex items-center gap-4">
                                    <span
                                        aria-hidden="true"
                                        className="h-0.5 w-8 shrink-0 bg-gold"
                                    />

                                    <Text
                                        as="p"
                                        variant="label"
                                        weight="medium"
                                        className="text-xs tracking-widest text-gold uppercase"
                                    >
                                        {eyebrow}
                                    </Text>
                                </div>
                            )}

                            <Text
                                as="h1"
                                variant="hero"
                                className="max-w-3xl text-4xl leading-[1.08] font-bold tracking-tight text-white sm:text-5xl md:text-6xl lg:text-7xl"
                            >
                                {titlePrefix}
                                {accentWord && (
                                    <>
                                        {' '}
                                        <span className="text-gold">
                                            {accentWord}
                                        </span>
                                    </>
                                )}
                            </Text>

                            {description && (
                                <Text
                                    as="p"
                                    variant="body-lg"
                                    className="max-w-2xl text-base leading-relaxed text-white/90 sm:text-lg"
                                >
                                    {description}
                                </Text>
                            )}

                            {hero.motto && (
                                <blockquote className="max-w-2xl border-s-2 border-gold py-1 ps-4">
                                    <Text
                                        as="p"
                                        variant="body"
                                        className="text-sm leading-relaxed text-white/90 italic sm:text-base"
                                    >
                                        “{hero.motto}”
                                    </Text>
                                </blockquote>
                            )}

                            {/* Primary and secondary actions */}
                            <div className="flex flex-wrap items-center gap-3 pt-2">
                                <Button
                                    asChild
                                    size="lg"
                                    className="h-12 rounded-full bg-red-700 px-6 text-white shadow-lg shadow-black/15 hover:bg-red-800 sm:h-14 sm:px-7"
                                >
                                    <Link href={jadwalMisa()}>
                                        <CalendarDays
                                            aria-hidden="true"
                                            className="size-5"
                                        />

                                        <Text
                                            as="span"
                                            variant="body"
                                            className="font-semibold"
                                        >
                                            Lihat Jadwal Misa
                                        </Text>

                                        <ArrowRight
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    </Link>
                                </Button>

                                <Button
                                    asChild
                                    size="lg"
                                    variant="outline"
                                    className="h-12 rounded-full border-white/60 bg-white/5 px-6 text-white backdrop-blur-sm hover:bg-white/15 hover:text-white sm:h-14 sm:px-7"
                                >
                                    <Link href={sejarah()}>
                                        <Church
                                            aria-hidden="true"
                                            className="size-5"
                                        />

                                        <Text
                                            as="span"
                                            variant="body"
                                            className="font-semibold"
                                        >
                                            Kenali Paroki
                                        </Text>

                                        <ArrowRight
                                            aria-hidden="true"
                                            className="size-4"
                                        />
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </div>
                </div>
            </PublicContainer>
        </section>
    );
}

export default HomeHero;
