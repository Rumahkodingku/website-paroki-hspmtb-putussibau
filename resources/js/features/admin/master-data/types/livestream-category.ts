export type LivestreamCategoryRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    livestreams_count?: number;
    can: { update: boolean; delete: boolean };
};

export type LivestreamCategoryFormData = {
    name: string;
    slug: string;
    description: string;
    sort_order: string;
    is_active: boolean;
};
