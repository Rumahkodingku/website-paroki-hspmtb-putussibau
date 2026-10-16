/*
 * Types only the Komunitas admin feature needs.
 *
 * Mirrors App\Http\Resources\Komunitas\CommunityListResource and
 * CommunityResource. See docs/DECISIONS.md D-55.
 */

export type CommunityStatusValue = 'forming' | 'active' | 'inactive';

export type StatusOption = {
    value: CommunityStatusValue;
    label: string;
};

export type CategoryOption = {
    id: number;
    name: string;
    slug: string;
};

export type CommunityRow = {
    id: number;
    name: string;
    slug: string;
    thumbnail_url: string | null;
    status: CommunityStatusValue;
    status_label: string;
    is_published: boolean;
    published_at: string | null;
    updated_at: string;
    category?: { id: number; name: string };
    can: { update: boolean; delete: boolean };
};

export type CommunityDetail = {
    id: number;
    name: string;
    slug: string;
    community_category_id: number;
    logo_media_id: number | null;
    logo_alt: string | null;
    logo_url: string | null;
    thumbnail_media_id: number | null;
    thumbnail_alt: string | null;
    thumbnail_url: string | null;
    description: string | null;
    vision: string | null;
    mission: string | null;
    meeting_schedule: string | null;
    meeting_location: string | null;
    eligibility: string | null;
    joining_info: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    show_contact: boolean;
    facebook: string | null;
    instagram: string | null;
    tiktok: string | null;
    youtube: string | null;
    website: string | null;
    status: CommunityStatusValue;
    is_published: boolean;
    published_at: string | null;
    updated_at: string;
    category?: { id: number; name: string };
    can: { update: boolean; delete: boolean };
};

export type CommunityFormData = {
    name: string;
    slug: string;
    community_category_id: string;
    logo_media_id: number | null;
    logo_alt: string;
    thumbnail_media_id: number | null;
    thumbnail_alt: string;
    description: string;
    vision: string;
    mission: string;
    meeting_schedule: string;
    meeting_location: string;
    eligibility: string;
    joining_info: string;
    contact_name: string;
    contact_phone: string;
    show_contact: boolean;
    facebook: string;
    instagram: string;
    tiktok: string;
    youtube: string;
    website: string;
    status: CommunityStatusValue;
    is_published: boolean;
};
