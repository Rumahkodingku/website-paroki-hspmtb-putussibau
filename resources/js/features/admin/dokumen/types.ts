export type DocumentStatus = 'draft' | 'published' | 'archived';

export type DocumentStatusOption = {
    value: DocumentStatus;
    label: string;
};

export type DocumentCategoryOption = {
    id: number;
    name: string;
};

export type DocumentCategoryRef = {
    id: number;
    name: string;
    slug: string;
};

export type DocumentRow = {
    id: number;
    title: string;
    category?: DocumentCategoryRef;
    extension: string;
    file_size: number;
    version_label: string | null;
    document_date: string | null;
    status: DocumentStatus;
    status_label: string;
    is_featured: boolean;
    is_expired: boolean;
    download_count: number;
    published_at: string | null;
    updated_at: string;
    can: {
        update: boolean;
        delete: boolean;
        preview: boolean;
    };
};

export type DocumentDetail = DocumentRow & {
    document_category_id: number;
    description: string | null;
    original_filename: string;
    mime_type: string;
    expires_at: string | null;
};

export type DocumentFormData = {
    title: string;
    document_category_id: string;
    description: string;
    version_label: string;
    document_date: string;
    expires_at: string;
    status: DocumentStatus;
    is_featured: boolean;
    file: File | null;
};

export type DocumentFilters = {
    search: string;
    kategori: string;
    status: string;
    sort: string;
    direction: string;
};

export type DocumentFormMeta = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
    empty: Record<string, string>;
    statuses: DocumentStatusOption[];
};
