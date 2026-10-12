/*
 * Types only the Berita admin feature needs.
 *
 * The shapes four features share live in types/news-agenda.ts; these three are
 * the ones deleting the Berita screens should take with them.
 */

import type { FormMeta, Option } from '@/types/news-agenda';

/** App\Enums\PostStatus */
export type PostStatus = 'draft' | 'scheduled' | 'published' | 'archived';

/** App\Http\Resources\News\PostResource — one row of the admin list. */
export type PostRow = {
    id: number;
    title: string;
    slug: string;
    post_category_id: number;
    /** Resolved on the server: what the admin typed, or the account's name. */
    author_name: string;
    excerpt: string | null;
    /** Sanitized HTML. Only ever rendered back into the editor. */
    content: string;
    featured_image: string | null;
    /** featured_image resolved to a browser URL, for the picker's preview. */
    featured_image_url: string | null;
    image_alt: string | null;
    scripture_reference: string | null;
    status: PostStatus;
    /** ISO 8601 in UTC; React renders it in the shared `displayTimezone`. */
    published_at: string | null;
    is_pinned: boolean;
    view_count: number;
    meta_title: string | null;
    meta_description: string | null;
    media_id: number | null;
    category: Option | null;
    can: { update: boolean; delete: boolean; preview: boolean };
};

/** What the article list reads back out of the URL. */
export type PostFilters = {
    search: string;
    status: string;
    kategori: string;
    sort: string;
    direction: string;
};

/** What the article form holds, and what it posts. */
export type PostFormData = {
    title: string;
    slug: string;
    post_category_id: number | null;
    author_name: string;
    excerpt: string;
    content: string;
    image_alt: string;
    scripture_reference: string;
    status: PostStatus;
    published_at: string;
    is_pinned: boolean;
    meta_title: string;
    meta_description: string;
    media_id: number | null;
};

export type PostFormMeta = Pick<FormMeta, 'labels' | 'hints' | 'statuses'> & {
    postStatusChoices: Array<{ value: PostStatus; label: string }>;
};
