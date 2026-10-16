/*
 * Types only the Kategori Komunitas admin feature needs.
 *
 * Mirrors App\Http\Resources\Komunitas\CommunityCategoryResource.
 * See docs/DECISIONS.md D-55.
 */

export type CommunityCategoryRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    /** Absent when the list did not count them — see D-39. */
    communities_count?: number;
    can: { update: boolean; delete: boolean };
};

export type CommunityCategoryFormData = {
    name: string;
    slug: string;
    description: string;
    sort_order: string;
    is_active: boolean;
};
