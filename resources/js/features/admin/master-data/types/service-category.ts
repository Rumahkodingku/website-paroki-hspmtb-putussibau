/*
 * Types only the Kategori Pelayanan admin feature needs.
 *
 * Mirrors App\Http\Resources\Pelayanan\ServiceCategoryResource. See
 * docs/DECISIONS.md D-54.
 */

export type ServiceCategoryRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    /** Absent when the list did not count them — see D-39. */
    services_count?: number;
    can: { update: boolean; delete: boolean };
};

export type ServiceCategoryFormData = {
    name: string;
    slug: string;
    description: string;
    sort_order: string;
    is_active: boolean;
};
