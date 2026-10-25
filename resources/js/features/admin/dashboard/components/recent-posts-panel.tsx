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
                    className={cn(
                        'inline-flex min-h-9 items-center gap-1.5 rounded-md',
                        'text-sm font-medium text-primary',
                        'transition-colors hover:text-primary/80',
                        'focus-visible:ring-2 focus-visible:outline-none',
                        'focus-visible:ring-ring focus-visible:ring-offset-2',
                    )}
                >
                    Lihat semua
                    <ArrowRight className="size-4" aria-hidden="true" />
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
                            className="text-sm font-medium text-primary underline-offset-4 hover:underline"
                        >
                            Tulis Berita
                        </Link>
                    }
                />
            ) : (
                <ul className="divide-y divide-border">
                    {posts.slice(0, 3).map((post) => (
                        <li key={post.id}>
                            <Link
                                href={contentEditHref('post', post.id)}
                                className={cn(
                                    'group flex min-w-0 items-center gap-3 px-4 py-3.5',
                                    'transition-colors hover:bg-muted/50',
                                    'focus-visible:relative focus-visible:z-10',
                                    'focus-visible:ring-2 focus-visible:outline-none',
                                    'focus-visible:ring-ring focus-visible:ring-inset',
                                    'sm:gap-4 sm:px-5 sm:py-4',
                                )}
                            >
                                {/* Thumbnail */}
                                {post.image ? (
                                    <ParishImage
                                        src={post.image}
                                        alt={post.image_alt ?? ''}
                                        width={128}
                                        height={128}
                                        aspectRatio="1 / 1"
                                        className={cn(
                                            'size-14 shrink-0 rounded-lg object-cover',
                                            'ring-1 ring-border/60 sm:size-16',
                                        )}
                                    />
                                ) : (
                                    <span
                                        className={cn(
                                            'flex size-14 shrink-0 items-center justify-center',
                                            'rounded-lg border border-border bg-muted/50',
                                            'text-muted-foreground sm:size-16',
                                        )}
                                        aria-hidden="true"
                                    >
                                        <Newspaper className="size-5" />
                                    </span>
                                )}

                                {/* News information */}
                                <span className="min-w-0 flex-1 space-y-2">
                                    <Text
                                        as="span"
                                        variant="label"
                                        lineClamp={2}
                                        className={cn(
                                            'block leading-snug text-foreground',
                                            'transition-colors group-hover:text-primary',
                                        )}
                                    >
                                        {post.title}
                                    </Text>

                                    <span className="flex min-w-0 flex-wrap items-center gap-x-2 gap-y-1.5">
                                        <ContentStatusBadge
                                            status={post.status}
                                            label={post.status_label}
                                        />

                                        <Text
                                            as="span"
                                            variant="fine-print"
                                            color="muted"
                                            className="leading-5 tabular-nums"
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
