export type DocumentCategoryRow = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    sort_order: number;
    is_active: boolean;
    documents_count: number;
    can: {
        update: boolean;
        delete: boolean;
    };
};

export type DocumentCategoryFormData = {
    name: string;
    slug: string;
    description: string;
    sort_order: string;
    is_active: boolean;
};

export type DocumentCategoryFilters = {
    search: string;
    sort: string;
    direction: string;
};
