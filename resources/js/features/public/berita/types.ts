/*
 * Types for the public Berita screens.
 *
 * Separate from the admin types because the shapes genuinely differ: a public
 * list card carries no `can`, no view count and no internal flags, and sending
 * the admin row to a visitor would put fields in the page source that no page
 * renders. That is ARCHITECTURE.md Part C rule 1.
 */

/** App\Http\Resources\Public\News\PublicPostCategoryResource */
export type PublicCategory = {
    id: number;
    name: string;
    slug: string;
};

/** App\Http\Resources\Public\News\PublicPostResource — one card in the list. */
export type PublicPost = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    image: string | null;
    image_alt: string | null;
    category: PublicCategory | null;
    /** ISO 8601 in UTC; rendered in the shared `displayTimezone`. */
    published_at: string | null;
};

/** App\Http\Resources\Public\News\PublicPostDetailResource */
export type PublicPostDetail = {
    id: number;
    title: string;
    slug: string;
    author_name: string;
    /** Sanitized HTML. Rendered only through RichText. */
    content: string;
    image: string | null;
    image_alt: string | null;
    scripture_reference: string | null;
    category: PublicCategory | null;
    published_at: string | null;
    view_count: number;
    seo: { title: string; description: string | null };
};

/** What the list page reads back out of the URL. */
export type PublicPostFilters = {
    q: string;
    kategori: string;
};
