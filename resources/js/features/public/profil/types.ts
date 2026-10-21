export type PublicHistoryItem = {
    id: number;
    title: string;
    year: number | null;
    date: string | null;
    description: string | null;
    image: string | null;
    image_alt: string | null;
};

export type PublicServiceArea = {
    content: string | null;
    map: string | null;
    map_url: string | null;
};

export type PublicPastor = {
    id: number;
    name: string;
    title: string | null;
    photo_media_id: number | null;
    photo: string | null;
    photo_alt: string | null;
    bio: string | null;
    sort_order: number;
    position: string | null;
    position_label: string | null;
    start_year: number | null;
    end_year: number | null;
    is_current: boolean | null;
    description: string | null;
};

export type PublicPastorAssignment = {
    id: number;
    name: string;
    title: string | null;
    photo: string | null;
    photo_alt: string | null;
    position: string;
    position_label: string;
    start_year: number | null;
    end_year: number | null;
    is_current: boolean;
    description: string | null;
};

export type PublicBoardMember = {
    id: number;
    name: string;
    position: string;
    photo: string | null;
    photo_alt: string | null;
    description: string | null;
    sort_order: number;
};

export type PublicBoardPeriod = {
    id: number;
    name: string;
    start_year: number | null;
    end_year: number | null;
    description: string | null;
    is_active: boolean;
    members: PublicBoardMember[];
};
/*
 * Types the public Area pages need.
 *
 * Mirrors App\Http\Resources\Public\Area\*. `leader_name` and `contact_phone`
 * are optional because the server omits them when the area's show_contact is
 * false (XC-P1) — the absence is the contract, not an empty value.
 *
 * See docs/DECISIONS.md D-57.
 */

export type PublicAreaCard = {
    id: number;
    name: string;
    slug: string;
    type: 'wilayah' | 'lingkungan' | 'stasi';
    type_label: string;
    /** Present only when the area allows its leader's contact. */
    leader_name?: string | null;
    contact_phone?: string | null;
};

export type PublicAreaSection = {
    type: string;
    label: string;
    areas: PublicAreaCard[];
};

export type PublicAreaChild = {
    id: number;
    name: string;
    slug: string;
    type: string;
    type_label: string;
    leader_name: string | null;
    contact_phone: string | null;
};

export type PublicAreaDetail = {
    id: number;
    name: string;
    slug: string;
    type: 'wilayah' | 'lingkungan' | 'stasi';
    type_label: string;
    description: string | null;
    address: string | null;
    /** Finished map link, or null; decided server-side by Area::mapsUrl(). */
    mapsUrl: string | null;
    parent: {
        id: number;
        name: string;
        slug: string;
        type: string;
        type_label: string;
    } | null;
    children: PublicAreaChild[];
    leader_name?: string | null;
    contact_phone?: string | null;
};
