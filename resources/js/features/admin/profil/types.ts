import type { Paginated } from '@/types';

export type ProfilFormMeta = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    positions: Array<{ value: string; label: string }>;
};

export type ProfilFilters = {
    search: string;
    sort: string;
    direction: string;
};

/* -------------------------------------------------------------------------- */
/* Sejarah                                                                    */
/* -------------------------------------------------------------------------- */

export type ParishHistoryItemRow = {
    id: number;
    title: string;
    year: number | null;
    date: string | null;
    description: string | null;
    media_id: number | null;
    image_alt: string | null;
    image_url: string | null;
    sort_order: number;
    is_published: boolean;
    can: { update: boolean; delete: boolean };
};

export type ParishHistoryItemFormData = {
    title: string;
    year: string;
    date: string;
    description: string;
    media_id: number | null;
    image_alt: string;
    sort_order: string;
    is_published: boolean;
};

/* -------------------------------------------------------------------------- */
/* Informasi Profil                                                           */
/* -------------------------------------------------------------------------- */

export type ParishProfileRow = {
    id: number | null;
    description: string | null;
    established_year: number | null;
    cover_media_id: number | null;
    cover_alt: string | null;
    cover_url: string | null;
    video_url: string | null;
    can: { update: boolean };
};

export type ParishProfileFormData = {
    description: string;
    established_year: string;
    cover_media_id: number | null;
    cover_alt: string;
    video_url: string;
};

/* -------------------------------------------------------------------------- */
/* Wilayah                                                                    */
/* -------------------------------------------------------------------------- */

export type ParishServiceAreaRow = {
    id: number | null;
    content: string | null;
    map_media_id: number | null;
    map_url: string | null;
    map_media_url: string | null;
    is_published: boolean;
    can: { update: boolean };
};

export type ParishServiceAreaFormData = {
    content: string;
    map_media_id: number | null;
    map_url: string;
    is_published: boolean;
};

/* -------------------------------------------------------------------------- */
/* Pastor                                                                     */
/* -------------------------------------------------------------------------- */

export type PastorAssignmentRow = {
    id: number;
    pastor_id: number;
    position: string;
    position_label: string;
    start_year: number | null;
    end_year: number | null;
    is_current: boolean;
    description: string | null;
    can: { update: boolean; delete: boolean };
};

export type PastorRow = {
    id: number;
    name: string;
    title: string | null;
    photo_media_id: number | null;
    photo_alt: string | null;
    photo_url: string | null;
    bio: string | null;
    sort_order: number;
    is_published: boolean;
    current_assignment: PastorAssignmentRow | null;
    assignments: PastorAssignmentRow[];
    assignments_count?: number;
    can: { update: boolean; delete: boolean };
};

export type PastorFormData = {
    name: string;
    title: string;
    photo_media_id: number | null;
    photo_alt: string;
    bio: string;
    sort_order: string;
    is_published: boolean;
};

export type PastorAssignmentFormData = {
    position: string;
    start_year: string;
    end_year: string;
    is_current: boolean;
    description: string;
};

/* -------------------------------------------------------------------------- */
/* Struktur kepengurusan                                                      */
/* -------------------------------------------------------------------------- */

export type ParishBoardMemberRow = {
    id: number;
    parish_board_period_id: number;
    name: string;
    position: string;
    photo_media_id: number | null;
    photo_alt: string | null;
    photo_url: string | null;
    description: string | null;
    sort_order: number;
    is_published: boolean;
    can: { update: boolean; delete: boolean };
};

export type ParishBoardPeriodRow = {
    id: number;
    name: string;
    start_year: number | null;
    end_year: number | null;
    description: string | null;
    is_active: boolean;
    is_published: boolean;
    sort_order: number;
    members_count?: number;
    members: ParishBoardMemberRow[];
    can: { update: boolean; delete: boolean };
};

export type ParishBoardPeriodFormData = {
    name: string;
    start_year: string;
    end_year: string;
    description: string;
    is_active: boolean;
    is_published: boolean;
    sort_order: string;
};

export type ParishBoardMemberFormData = {
    name: string;
    position: string;
    photo_media_id: number | null;
    photo_alt: string;
    description: string;
    sort_order: string;
    is_published: boolean;
};

export type PaginatedHistory = Paginated<ParishHistoryItemRow>;
export type PaginatedPastors = Paginated<PastorRow>;
