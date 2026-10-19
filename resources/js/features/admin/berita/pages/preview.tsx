import { Link } from '@inertiajs/react';
import { Alert } from '@/components/ui/alert';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { RichText } from '@/components/rich-text';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { PublicSection } from '@/components/public-section';
import { formatWibDate } from '@/lib/datetime';
import { home } from '@/routes';
import { index as postsIndex } from '@/routes/posts';
import { usePage } from '@inertiajs/react';

type Props = {
    post: {
        id: number;
        title: string;
        slug: string;
        author_name: string;
        content: string;
        image: string | null;
        image_alt: string | null;
        scripture_reference: string | null;
        published_at: string | null;
        seo: { title: string; description: string | null };
        category: { id: number; name: string; slug: string } | null;
    };
    isPublished: boolean;
};

export default function Preview({ post, isPublished }: Props) {
    const { displayTimezone } = usePage().props;

    return (
        <>
            <Seo
                title={post.seo.title}
                description={post.seo.description}
                noIndex
            />

            <PublicSection>
                <Breadcrumbs
                    className="mb-8"
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Berita', href: postsIndex() },
                        { title: post.title },
                    ]}
                />

                {!isPublished ? (
                    <Alert className="mb-8">
                        <Text as="p" variant="heading-sm">
                            Pratinjau
                        </Text>
                        <Text as="p" variant="body-sm">
                            Artikel ini belum tayang di halaman publik. Halaman
                            ini hanya untuk admin dan tidak akan ditemukan mesin
                            pencari.
                        </Text>
                    </Alert>
                ) : null}

                <article className="mx-auto max-w-3xl space-y-6">
                    <header className="space-y-3">
                        {post.category ? (
                            <Text as="p" variant="heading-md" color="brand">
                                {post.category.name}
                            </Text>
                        ) : null}

                        <Text as="h1" variant="h1">
                            {post.title}
                        </Text>

                        <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                            <Text as="p" variant="body-sm" color="muted">
                                {post.author_name}
                            </Text>
                            {post.published_at ? (
                                <Text as="p" variant="body-sm" color="muted">
                                    {formatWibDate(
                                        post.published_at,
                                        displayTimezone,
                                    )}
                                </Text>
                            ) : null}
                        </div>
                    </header>

                    {post.image ? (
                        <img
                            src={post.image}
                            alt={post.image_alt ?? ''}
                            className="w-full rounded-md object-cover"
                        />
                    ) : null}

                    {post.scripture_reference ? (
                        <blockquote className="border-l-4 border-primary pl-4">
                            <Text as="p" variant="heading-sm">
                                {post.scripture_reference}
                            </Text>
                        </blockquote>
                    ) : null}

                    <RichText html={post.content} />

                    <footer className="flex justify-end border-t border-border pt-6">
                        <Button asChild variant="outline">
                            <Link href={postsIndex()}>Kembali ke daftar</Link>
                        </Button>
                    </footer>
                </article>
            </PublicSection>
        </>
    );
}
