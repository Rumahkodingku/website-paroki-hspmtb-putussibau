import { Link } from '@inertiajs/react';
import Image from '@/components/image';
import { ArrowRight } from 'lucide-react';
import { Text } from '@/components/text';
import { show as post } from '@/routes/berita';
import { formatWibDate } from '@/lib/datetime';
import type { PublicPost } from '../types';
import { Badge } from '@/components/ui/badge';

export function PinnedStrip({
    articles,
    displayTimezone,
}: {
    articles: PublicPost[];
    displayTimezone: string;
}) {
    if (articles.length === 0) {
        return null;
    }

    const [leadArticle, secondaryArticle] = articles;

    return (
        <section aria-labelledby="berita-semat" className="space-y-4">
            <div className="flex items-end justify-between gap-4">
                <Text as="h2" variant="h3" id="berita-semat">
                    Sorotan Pilihan Redaksi
                </Text>

                <Text
                    as="p"
                    variant="body-sm"
                    color="muted"
                    className="hidden shrink-0 sm:block"
                >
                    Kabar pilihan dari paroki
                </Text>
            </div>

            <div className="grid gap-4 lg:grid-cols-[minmax(0,1.7fr)_minmax(17rem,0.8fr)]">
                {/* Lead story */}
                <article className="group relative isolate flex min-h-88 overflow-hidden rounded-xl bg-navy md:min-h-100">
                    {leadArticle.image ? (
                        <Image
                            src={leadArticle.image}
                            alt={leadArticle.image_alt ?? ''}
                            loading="eager"
                            fetchPriority="high"
                            fit="cover"
                            imageClassName="transition-transform duration-500 group-hover:scale-[1.03]"
                            className="inset-0 -z-20 h-full w-full"
                            position="absolute"
                        />
                    ) : null}

                    <div
                        aria-hidden="true"
                        className="absolute inset-0 -z-10 bg-linear-to-t from-black/85 via-black/35 to-black/5"
                    />

                    <div className="mt-auto w-full space-y-3 p-5 sm:p-7 lg:p-8">
                        <div className="flex flex-wrap items-center gap-2 text-white">
                            {leadArticle.category ? (
                                <Badge variant="secondary" className="text-xs">
                                    {leadArticle.category.name}
                                </Badge>
                            ) : null}

                            {leadArticle.published_at ? (
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    className="text-white/85"
                                >
                                    {formatWibDate(
                                        leadArticle.published_at,
                                        displayTimezone,
                                    )}
                                </Text>
                            ) : null}
                        </div>

                        <Text
                            as="h3"
                            variant="h2"
                            className="max-w-2xl text-balance text-white"
                        >
                            {leadArticle.title}
                        </Text>

                        {leadArticle.excerpt ? (
                            <Text
                                as="p"
                                variant="body"
                                className="line-clamp-2 max-w-xl text-white/85"
                            >
                                {leadArticle.excerpt}
                            </Text>
                        ) : null}

                        <span className="inline-flex items-center gap-2 text-sm font-semibold text-white">
                            Baca selengkapnya
                            <ArrowRight
                                aria-hidden="true"
                                className="size-4 transition-transform group-hover:translate-x-1"
                            />
                        </span>
                    </div>

                    <Link
                        href={post(leadArticle.slug)}
                        prefetch
                        aria-label={`Baca artikel: ${leadArticle.title}`}
                        className="absolute inset-0 z-10 rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    />
                </article>

                {/* Secondary story */}
                {secondaryArticle ? (
                    <article className="group relative flex min-w-0 flex-col overflow-hidden rounded-xl border border-border transition-shadow">
                        {secondaryArticle.image ? (
                            <div className="overflow-hidden">
                                <Image
                                    src={secondaryArticle.image}
                                    alt={secondaryArticle.image_alt ?? ''}
                                    loading="lazy"
                                    aspectRatio="16 / 9"
                                    fit="cover"
                                    imageClassName="transition-transform duration-500 group-hover:scale-[1.03]"
                                    className="w-full"
                                />
                            </div>
                        ) : (
                            <div
                                aria-hidden="true"
                                className="aspect-video w-full bg-muted"
                            />
                        )}

                        <div className="flex flex-1 flex-col gap-3 p-4 sm:p-5">
                            <div className="flex flex-wrap items-center gap-2">
                                {secondaryArticle.category ? (
                                    <Badge variant="secondary">
                                        {secondaryArticle.category.name}
                                    </Badge>
                                ) : null}

                                {secondaryArticle.published_at ? (
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                    >
                                        {formatWibDate(
                                            secondaryArticle.published_at,
                                            displayTimezone,
                                        )}
                                    </Text>
                                ) : null}
                            </div>

                            <Text as="h3" variant="h4" className="text-balance">
                                {secondaryArticle.title}
                            </Text>

                            {secondaryArticle.excerpt ? (
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="line-clamp-3"
                                >
                                    {secondaryArticle.excerpt}
                                </Text>
                            ) : null}

                            <span className="mt-auto inline-flex items-center gap-2 pt-1 text-sm font-medium">
                                Baca selengkapnya
                                <ArrowRight
                                    aria-hidden="true"
                                    className="size-4 transition-transform group-hover:translate-x-1"
                                />
                            </span>
                        </div>

                        <Link
                            href={post(secondaryArticle.slug)}
                            prefetch
                            aria-label={`Baca artikel: ${secondaryArticle.title}`}
                            className="absolute inset-0 z-10 rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        />
                    </article>
                ) : null}
            </div>
        </section>
    );
}
