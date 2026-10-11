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
    /** The stored hex. Never rendered directly — see color_value. */
    color: string;
    /**
     * The same colour already shaped into a CSS value, or null when it is not a
     * valid hex. This is the only one a component may use: a hex written in a
     * .tsx file is a colour that cannot be re-themed, which is what
     * ArchitectureTest::'no component reaches for a brand colour by hex value'
     * exists to prevent. See EventCategory::colorValue().
     */
    color_value: string | null;
    /** Absent when the list did not count them. See D-39. */
    events_count?: number;
    can: { update: boolean; delete: boolean };
};

/** What the form holds, and what it posts. */
export type EventCategoryFormData = {
    name: string;
    slug: string;
    color: string;
};
