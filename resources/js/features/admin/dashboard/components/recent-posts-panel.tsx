import { Link } from '@inertiajs/react';
import { ArrowRight, Newspaper } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { ParishImage } from '@/components/parish-image';
import { Text } from '@/components/text';
import { formatWibDate } from '@/lib/datetime';
import { create as createPost, list as postsList } from '@/routes/posts';
import { cn } from '@/lib/utils';
import { contentEditHref } from '../content-target';
import type { RecentPost } from '../types';
import { ContentStatusBadge } from './content-status-badge';
import { Panel } from './panel';

/*
 * Berita Terbaru: the three newest published articles.
 *
 * The thumbnail is the article's own featured_image, the same picture the
 * public card shows, and it falls back to a neutral icon tile when the article
 * has no picture. There is no stock image and no invented URL — a missing image
 * is simply a missing image.
 */
export function RecentPostsPanel({
    posts,
    displayTimezone,
    className,
}: {
    posts: RecentPost[];
    displayTimezone: string;
    className?: string;
}) {
    return (
        <Panel
            title="Berita Terbaru"
            className={cn('h-full', className)}
            action={
                <Link
                    href={postsList().url}
                    className="inline-flex items-center gap-1 text-label text-primary-on-surface underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    Lihat semua
                    <ArrowRight className="size-3.5" aria-hidden="true" />
                </Link>
            }
        >
            {posts.length === 0 ? (
                <EmptyState
                    icon={Newspaper}
                    title="Belum ada berita terbit"
                    description="Artikel yang sudah dipublikasikan akan muncul di sini."
                    action={
                        <Link
                            href={createPost().url}
                            className="text-label text-primary-on-surface underline-offset-4 hover:underline"
                        >
                            Tulis Berita
                        </Link>
                    }
                />
            ) : (
                <ul className="divide-y divide-border">
                    {posts.map((post) => (
                        <li key={post.id}>
                            <Link
                                href={contentEditHref('post', post.id)}
                                className="flex items-start gap-3 px-5 py-4 transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                {post.image ? (
                                    <ParishImage
                                        src={post.image}
                                        alt={post.image_alt ?? ''}
                                        width={96}
                                        height={96}
                                        aspectRatio="1 / 1"
                                        className="size-14 shrink-0 rounded-md"
                                    />
                                ) : (
                                    <span
                                        className="flex size-14 shrink-0 items-center justify-center rounded-md bg-secondary text-subtle"
                                        aria-hidden="true"
                                    >
                                        <Newspaper className="size-5" />
                                    </span>
                                )}

                                <span className="min-w-0 flex-1 space-y-1.5">
                                    <Text
                                        as="span"
                                        variant="label"
                                        lineClamp={2}
                                        className="block"
                                    >
                                        {post.title}
                                    </Text>

                                    <span className="flex flex-wrap items-center gap-2">
                                        <ContentStatusBadge
                                            type="post"
                                            status={post.status}
                                            label={post.status_label}
                                        />
                                        <Text
                                            as="span"
                                            variant="fine-print"
                                            color="muted"
                                            className="tabular-nums"
                                        >
                                            {formatWibDate(
                                                post.published_at ??
                                                    post.updated_at,
                                                displayTimezone,
                                            )}
                                        </Text>
                                    </span>
                                </span>
                            </Link>
                        </li>
                    ))}
                </ul>
            )}
        </Panel>
    );
}
