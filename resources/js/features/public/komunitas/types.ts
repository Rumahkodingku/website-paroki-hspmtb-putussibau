/*
 * Types the public Komunitas pages need.
 *
 * Mirrors App\Http\Resources\Public\Komunitas\*. `contact` is optional because
 * the server omits the key entirely when show_contact is false (XC-P1) — the
 * absence is the contract, not an empty value.
 *
 * See docs/DECISIONS.md D-55.
 */

export type PublicCommunityCategory = {
    id: number;
    name: string;
    slug: string;
};

export type PublicCommunityCard = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    thumbnail_url: string | null;
    category?: PublicCommunityCategory;
};

export type PublicCommunityDetail = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    vision: string | null;
    mission: string | null;
    meeting_schedule: string | null;
    meeting_location: string | null;
    eligibility: string | null;
    joining_info: string | null;
    thumbnail_url: string | null;
    logo_url: string | null;
    category?: PublicCommunityCategory;
    /** Absent when show_contact is false, or when there is no contact at all. */
    contact?: {
        name: string | null;
        phone: string | null;
    };
    /**
     * The community's own links, already filtered to the filled ones.
     *
     * Optional because the server omits the key entirely when every column is
     * empty (D-59) - the absence is the contract, not an empty array.
     */
    socials?: {
        label: string;
        url: string;
    }[];
    /** The parish secretariat as a CTA target; null when no number is set. */
    secretariat: {
        phone: string | null;
        tel: string | null;
        whatsapp: string | null;
    } | null;
};

export type PublicCommunityFilters = {
    q: string;
    kategori: string;
};
