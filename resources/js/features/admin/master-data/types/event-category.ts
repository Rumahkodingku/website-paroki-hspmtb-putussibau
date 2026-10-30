/*
 * Types only the Kategori Agenda admin feature needs.
 *
 * The shapes four features share live in types/news-agenda.ts.
 */

/** App\Http\Resources\Agenda\EventCategoryResource — one row of the list. */
export type EventCategoryRow = {
    id: number;
    name: string;
    slug: string;
    /** Absent when the list did not count them. See D-39. */
    events_count?: number;
    can: { update: boolean; delete: boolean };
};

/** What the form holds, and what it posts. */
export type EventCategoryFormData = {
    name: string;
    slug: string;
};
