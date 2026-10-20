import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { PublicSection } from '@/components/public-section';
import { Text } from '@/components/text';
import { formatWibDate } from '@/lib/datetime';
import { show as post } from '@/routes/berita';
import type { ArticleCardData } from '@/components/article-card';

export function HomeDevotionSection({
    devotion,
    enabled,
    displayTimezone,
}: {
    devotion: ArticleCardData | null;
    enabled: boolean;
    displayTimezone: string;
}) {
    if (!enabled || devotion === null) {
        return null;
    }

    return (
        <PublicSection surface="soft">
            <div className="grid gap-10 lg:grid-cols-[0.8fr_2fr] lg:gap-16">
                <div className="space-y-3">
                    <Text as="p" variant="label" color="danger">
                        Renungan
                    </Text>
                    <Text as="h2" variant="display-md">
                        Renungan Terbaru
                    </Text>
                </div>

                <article className="border-t border-border pt-6 lg:border-s lg:border-t-0 lg:ps-12 lg:pt-0">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                        {devotion.published_at ? (
                            <Text as="p" variant="body-sm" color="muted">
                                {formatWibDate(
                                    devotion.published_at,
                                    displayTimezone,
                                )}
                            </Text>
                        ) : null}
                        {devotion.category ? (
                            <Text as="p" variant="label" color="danger">
                                {devotion.category.name}
                            </Text>
                        ) : null}
                    </div>

                    <Text as="h3" variant="display-md" className="mt-4">
                        {devotion.title}
                    </Text>

                    {devotion.excerpt ? (
                        <Text
                            as="p"
                            variant="body-lg"
                            color="muted"
                            className="mt-4 max-w-2xl"
                        >
                            {devotion.excerpt}
                        </Text>
                    ) : null}

                    <Link
                        href={post(devotion.slug)}
                        prefetch
                        className="mt-6 inline-flex min-h-11 items-center gap-2 text-heading-sm text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                    >
                        Baca renungan
                        <ArrowRight className="size-4" aria-hidden="true" />
                    </Link>
                </article>
            </div>
        </PublicSection>
    );
}

export default HomeDevotionSection;
