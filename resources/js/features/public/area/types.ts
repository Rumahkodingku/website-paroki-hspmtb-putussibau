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
