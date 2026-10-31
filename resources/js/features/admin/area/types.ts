export type AreaTypeValue = 'wilayah' | 'lingkungan' | 'stasi';

export type AreaRow = {
    id: number;
    parent_id: number | null;
    name: string;
    slug: string;
    type: AreaTypeValue;
    type_label: string;
    description: string | null;
    address: string | null;
    latitude: string | null;
    longitude: string | null;
    maps_url: string | null;
    leader_name: string | null;
    contact_phone: string | null;
    contact_email: string | null;
    contact_whatsapp: string | null;
    photo_media_id: number | null;
    photo_url: string | null;
    show_contact: boolean;
    sort_order: number;
    is_active: boolean;
    parent?: {
        id: number;
        name: string;
        type: AreaTypeValue;
    } | null;
    can: {
        update: boolean;
        delete: boolean;
    };
};

export type AreaOption = {
    id: number;
    name: string;
    slug: string;
    type: AreaTypeValue;
    type_label: string;
    parent_id: number | null;
    parent_name?: string | null;
};

export type AreaTypeOption = {
    value: AreaTypeValue;
    label: string;
};

export type AreaFormData = {
    name: string;
    slug: string;
    type: AreaTypeValue;
    parent_id: string;
    description: string;
    address: string;
    latitude: string;
    longitude: string;
    maps_url: string;
    leader_name: string;
    contact_phone: string;
    contact_email: string;
    contact_whatsapp: string;
    photo_media_id: string;
    show_contact: boolean;
    sort_order: string;
    is_active: boolean;
};
