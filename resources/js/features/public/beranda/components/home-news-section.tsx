import { Link } from '@inertiajs/react';
import { ArrowRight, CalendarDays, Star } from 'lucide-react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Text } from '@/components/text';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { index as berita } from '@/routes/berita';

type HomeNewsArticle = {
    id: number;
    title: string;
    slug: string;
    excerpt?: string | null;
    image?: string | null;
    image_alt?: string | null;
    imageAlt?: string | null;
    imageUrl?: string | null;
    published_at?: string | null;
    publishedAt?: string | null;
    category?: {
        id: number;
        name: string;
        slug: string;
    } | null;
};

type HomeNewsSectionProps = {
    news: HomeNewsArticle[];
    displayTimezone: string;
};

function formatPublishedDate(
    date: string | null | undefined,
    timeZone: string,
) {
    if (!date) return null;

    const parsedDate = new Date(date);

    if (Number.isNaN(parsedDate.getTime())) {
        return null;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        timeZone,
    }).format(parsedDate);
}

function getArticleData(article: HomeNewsArticle) {
    return {
        image: article.image ?? article.imageUrl ?? null,
        imageAlt: article.image_alt ?? article.imageAlt ?? article.title,
        publishedAt: article.published_at ?? article.publishedAt ?? null,
    };
}

function CategoryLabel({ name }: { name: string }) {
    return (
        <Text
            as="span"
            variant="label"
            color="default"
            weight="semibold"
            className="inline-flex w-fit items-center rounded-full bg-muted px-3 py-1 italic"
        >
            {name}
        </Text>
    );
}

function ArticleImage({ src, alt }: { src: string | null; alt: string }) {
    return (
        <div className="absolute inset-0 overflow-hidden bg-muted">
            {src ? (
                <img
                    src={src}
                    alt={alt}
                    loading="lazy"
                    decoding="async"
                    className="size-full object-cover transition-transform duration-500 ease-out group-hover:scale-[1.03] motion-reduce:transform-none motion-reduce:transition-none"
                />
            ) : (
                <div
                    aria-hidden="true"
                    className="flex size-full items-center justify-center bg-muted"
                >
                    <Text
                        as="span"
                        variant="display-lg"
                        color="muted"
                        className="font-serif opacity-40"
                    >
                        H
                    </Text>
                </div>
            )}
        </div>
    );
}

function ArticleDate({
    date,
    displayTimezone,
    compact = false,
}: {
    date: string | null;
    displayTimezone: string;
    compact?: boolean;
}) {
    if (!date) return null;

    const formattedDate = formatPublishedDate(date, displayTimezone);

    if (!formattedDate) return null;

    return (
        <div className="inline-flex min-w-0 items-center gap-1.5">
            <CalendarDays
                aria-hidden="true"
                className={`shrink-0 text-muted-foreground ${
                    compact ? 'size-3.5' : 'size-4'
                }`}
            />

            <Text
                as="time"
                variant={compact ? 'caption' : 'body-sm'}
                color="muted"
                dateTime={date}
            >
                {formattedDate}
            </Text>
        </div>
    );
}

function ReadMoreLink() {
    return (
        <>
            <Text
                as="span"
                variant="body-sm"
                color="primary"
                weight="medium"
                className="transition-opacity group-hover:opacity-75"
            >
                Baca selengkapnya
            </Text>

            <ArrowRight
                aria-hidden="true"
                className="size-4 shrink-0 transition-transform duration-200 group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
            />
        </>
    );
}

function ArticleMeta({
    article,
    displayTimezone,
    compact = false,
}: {
    article: HomeNewsArticle;
    displayTimezone: string;
    compact?: boolean;
}) {
    const { publishedAt } = getArticleData(article);

    return (
        <div className="flex flex-col items-start gap-2.5">
            {article.category && <CategoryLabel name={article.category.name} />}

            <ArticleDate
                date={publishedAt}
                displayTimezone={displayTimezone}
                compact={compact}
            />
        </div>
    );
}

function FeaturedArticle({
    article,
    displayTimezone,
}: {
    article: HomeNewsArticle;
    displayTimezone: string;
}) {
    const articleData = getArticleData(article);

    return (
        <article className="group mb-5 grid min-w-0 overflow-hidden rounded-2xl lg:grid-cols-[1.2fr_1fr]">
            {/* Featured image */}
            <Link
                href={`/berita/${article.slug}`}
                aria-label={`Baca berita: ${article.title}`}
                className="relative block aspect-4/3 min-w-0 overflow-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset sm:aspect-video lg:aspect-auto lg:min-h-80"
            >
                <ArticleImage
                    src={articleData.image}
                    alt={articleData.imageAlt}
                />

                <div className="absolute top-4 left-4 inline-flex items-center gap-2 rounded-full bg-primary px-3.5 py-2 shadow-md sm:top-5 sm:left-5">
                    <Star
                        aria-hidden="true"
                        className="size-3.5 fill-current text-primary-foreground"
                    />

                    <Text
                        as="span"
                        variant="label"
                        color="inherit"
                        weight="medium"
                        className="text-primary-foreground"
                    >
                        Berita Terbaru
                    </Text>
                </div>
            </Link>

            {/* Featured content */}
            <div className="flex min-w-0 flex-col justify-center p-5 sm:p-7 lg:p-8 xl:p-10">
                <ArticleMeta
                    article={article}
                    displayTimezone={displayTimezone}
                />

                <Text
                    as="h3"
                    variant="h3"
                    weight="semibold"
                    className="mt-5 text-xl leading-snug tracking-tight sm:text-2xl lg:text-3xl"
                >
                    <Link
                        href={`/berita/${article.slug}`}
                        className="rounded-sm transition-opacity outline-none hover:opacity-75 focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        {article.title}
                    </Link>
                </Text>

                {article.excerpt && (
                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        lineClamp={3}
                        className="mt-3 leading-relaxed"
                    >
                        {article.excerpt}
                    </Text>
                )}

                <div className="mt-5">
                    <TextLink
                        href={`/berita/${article.slug}`}
                        className="group/link inline-flex min-h-11 items-center gap-2 rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        aria-label={`Baca selengkapnya: ${article.title}`}
                    >
                        <ReadMoreLink />
                    </TextLink>
                </div>
            </div>
        </article>
    );
}

function SecondaryArticleCard({
    article,
    displayTimezone,
}: {
    article: HomeNewsArticle;
    displayTimezone: string;
}) {
    const articleData = getArticleData(article);

    return (
        <article className="group flex min-w-0 flex-col overflow-hidden rounded-2xl">
            {/* Card image */}
            <Link
                href={`/berita/${article.slug}`}
                aria-label={`Baca berita: ${article.title}`}
                className="relative block aspect-video w-full shrink-0 overflow-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
            >
                <ArticleImage
                    src={articleData.image}
                    alt={articleData.imageAlt}
                />
            </Link>

            {/* Card content */}
            <div className="flex flex-1 flex-col p-4 sm:p-5">
                <ArticleMeta
                    article={article}
                    displayTimezone={displayTimezone}
                    compact
                />

                <Text
                    as="h3"
                    variant="h4"
                    weight="semibold"
                    className="mt-3 leading-snug tracking-tight"
                >
                    <Link
                        href={`/berita/${article.slug}`}
                        className="rounded-sm transition-opacity outline-none hover:opacity-75 focus-visible:ring-2 focus-visible:ring-ring"
                    >
                        {article.title}
                    </Link>
                </Text>

                {article.excerpt && (
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        lineClamp={2}
                        className="mt-2 leading-6"
                    >
                        {article.excerpt}
                    </Text>
                )}

                <div className="mt-auto pt-4">
                    <TextLink
                        href={`/berita/${article.slug}`}
                        className="group/link inline-flex min-h-8 items-center gap-2 rounded-sm outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        aria-label={`Baca selengkapnya: ${article.title}`}
                    >
                        <ReadMoreLink />
                    </TextLink>
                </div>
            </div>
        </article>
    );
}

export function HomeNewsSection({
    news,
    displayTimezone,
}: HomeNewsSectionProps) {
    if (news.length === 0) {
        return null;
    }

    const [featured, ...secondaryArticles] = news;

    return (
        <PublicSection>
            <section
                aria-labelledby="home-news-heading"
                className="relative isolate"
            >
                <div className="space-y-8 md:space-y-10">
                    {/* Section heading */}
                    <SectionHeading
                        eyebrow="Kabar Paroki"
                        title="Berita & Artikel"
                        description="Kabar, kegiatan, dan renungan dari kehidupan menggereja di Paroki HSPMTB Putussibau."
                        action={
                            <Button
                                asChild
                                variant="outline"
                                className="group h-11 rounded-full border-primary/70 bg-transparent px-5 text-primary transition-colors hover:bg-primary hover:text-primary-foreground"
                            >
                                <Link href={berita()}>
                                    Semua Berita
                                    <ArrowRight
                                        aria-hidden="true"
                                        className="size-4 transition-transform group-hover:translate-x-1 motion-reduce:transform-none motion-reduce:transition-none"
                                    />
                                </Link>
                            </Button>
                        }
                    />

                    {/* Featured story */}
                    <FeaturedArticle
                        article={featured}
                        displayTimezone={displayTimezone}
                    />

                    {/* Supporting news grid */}
                    {secondaryArticles.length > 0 && (
                        <div className="grid grid-cols-1 items-stretch gap-5 sm:grid-cols-2 xl:grid-cols-3">
                            {secondaryArticles.map((article) => (
                                <SecondaryArticleCard
                                    key={article.id}
                                    article={article}
                                    displayTimezone={displayTimezone}
                                />
                            ))}
                        </div>
                    )}
                </div>
            </section>
        </PublicSection>
    );
}

export default HomeNewsSection;
