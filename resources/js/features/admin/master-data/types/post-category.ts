/*
 * Types only the Kategori Berita admin feature needs.
 *
 * The shapes four features share live in types/news-agenda.ts.
 */

/** App\Http\Resources\News\PostCategoryResource — one row of the list. */
export type PostCategoryRow = {
    id: number;
    name: string;
    slug: string;
    sort_order: number;
    /**
     * Absent when the list did not count them. Showing 0 for "we did not look"
     * would understate what stands in the way of a delete. See D-39.
     */
    posts_count?: number;
    can: { update: boolean; delete: boolean };
};

/** What the form holds, and what it posts. */
export type PostCategoryFormData = {
    name: string;
    slug: string;
    sort_order: number;
};
