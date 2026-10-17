export type PublicDocumentCategory = {
    id: number;
    name: string;
    slug: string;
};

export type PublicDocumentCard = {
    id: number;
    title: string;
    description: string | null;
    category?: {
        id: number;
        name: string;
        slug: string;
    };
    extension: string;
    file_size: number;
    version_label: string | null;
    document_date: string | null;
    is_featured: boolean;
    is_expired: boolean;
    is_pdf: boolean;
    published_at: string | null;
    updated_at: string;
};
