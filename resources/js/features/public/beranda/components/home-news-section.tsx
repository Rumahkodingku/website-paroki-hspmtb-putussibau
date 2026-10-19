import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { ArticleCard, type ArticleCardData } from '@/components/article-card';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Button } from '@/components/ui/button';
import { index as berita } from '@/routes/berita';

/**
 * The latest published articles.
 *
 * The section disappears entirely when there is nothing to show. A heading with
 * an empty grid under it reads as a broken page, and the homepage already has
 * other blocks that can carry the visitor's attention.
 */
export function HomeNewsSection({
    news,
    displayTimezone,
}: {
    news: ArticleCardData[];
    displayTimezone: string;
}) {
    if (news.length === 0) {
        return null;
    }

    return (
        <PublicSection>
            <div className="space-y-8">
                <SectionHeading
                    title="Berita Terbaru"
                    description="Kabar, laporan kegiatan, dan renungan dari paroki."
                    action={
                        <Button asChild variant="outline">
                            <Link href={berita()}>
                                Semua Berita
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    }
                />

                <div
                    className={
                        news.length === 1
                            ? 'max-w-xl'
                            : 'grid gap-6 sm:grid-cols-2 lg:grid-cols-3'
                    }
                >
                    {news.map((article) => (
                        <ArticleCard
                            key={article.id}
                            article={article}
                            displayTimezone={displayTimezone}
                        />
                    ))}
                </div>
            </div>
        </PublicSection>
    );
}

export default HomeNewsSection;
