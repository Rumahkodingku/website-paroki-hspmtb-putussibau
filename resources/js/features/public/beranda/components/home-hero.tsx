import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { useState } from 'react';
import { PublicContainer } from '@/components/public-container';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { index as jadwalMisa } from '@/routes/jadwal-misa';
import { sejarah } from '@/routes/profil';
import type { IdentityDetails } from '@/types';
import type { HomeHero as HomeHeroData } from '../types';

/**
 * The homepage hero.
 *
 * The photograph is the first thing a visitor sees, so it carries the block and
 * the text sits on it rather than beside it. A single dark scrim exists only to
 * keep the copy legible; there is no decorative gradient, glow or animation.
 * The fallback behind a failed image is the navy surface the parish already
 * uses for editorial bands, so a missing file leaves a readable hero rather
 * than a broken one.
 */
export function HomeHero({
    hero,
    identity,
}: {
    hero: HomeHeroData;
    identity: IdentityDetails;
}) {
    const [imageFailed, setImageFailed] = useState(false);

    const title = hero.title ?? identity.name ?? 'Paroki HSPMTB Putussibau';
    const eyebrow = identity.tagline ?? identity.shortName;
    const description = hero.description ?? identity.shortDescription;

    return (
        <section className="relative isolate overflow-hidden bg-navy">
            {!imageFailed ? (
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
            ) : null}

            {/*
             * Two layers rather than one flat black sheet: the left side needs
             * more weight because the copy is left aligned, while the right
             * keeps enough of the church and statue visible to read as a place
             * rather than a stock silhouette.
             */}
            <div className="absolute inset-0 bg-black/35" aria-hidden="true" />
            <div
                className="absolute inset-0 bg-gradient-to-r from-black/75 via-black/45 to-black/10"
                aria-hidden="true"
            />

            <PublicContainer className="relative">
                <div className="flex min-h-[560px] items-center py-16 md:min-h-[640px] md:py-24 lg:min-h-[700px]">
                    <div className="max-w-3xl space-y-6">
                        {eyebrow ? (
                            <Text as="p" variant="label" className="text-gold">
                                {eyebrow}
                            </Text>
                        ) : null}

                        {/*
                         * `hero` is the fluid display-xl step (clamp 36–52px),
                         * so no responsive size overrides are needed.
                         */}
                        <Text as="h1" variant="hero" className="text-white">
                            {title}
                        </Text>

                        {description ? (
                            <Text
                                as="p"
                                variant="body-lg"
                                className="max-w-2xl text-white/90"
                            >
                                {description}
                            </Text>
                        ) : null}

                        {hero.motto ? (
                            <Text
                                as="blockquote"
                                variant="body"
                                className="max-w-2xl border-s-2 border-gold ps-4 text-white/85 italic"
                            >
                                {hero.motto}
                            </Text>
                        ) : null}

                        <div className="flex flex-wrap items-center gap-3 pt-2">
                            <Button asChild size="lg">
                                <Link href={jadwalMisa()}>
                                    Lihat Jadwal Misa
                                    <ArrowRight aria-hidden="true" />
                                </Link>
                            </Button>

                            <Button
                                asChild
                                size="lg"
                                variant="outline"
                                className="border-white/50 bg-white/10 text-white hover:bg-white/20 hover:text-white"
                            >
                                <Link href={sejarah()}>Sejarah Paroki</Link>
                            </Button>
                        </div>
                    </div>
                </div>
            </PublicContainer>
        </section>
    );
}

export default HomeHero;
