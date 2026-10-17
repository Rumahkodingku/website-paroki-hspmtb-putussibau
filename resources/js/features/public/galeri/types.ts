/*
 * Types the public Galeri pages need.
 *
 * Mirrors App\Http\Resources\Public\Galeri\*. See docs/DECISIONS.md D-65.
 */

export type PublicGalleryPhoto = {
    id: number;
    caption: string | null;
    alt_text: string | null;
    url: string | null;
    thumb_url: string | null;
    large_url: string | null;
    width: number | null;
    height: number | null;
};

export type PublicGalleryCard = {
    id: number;
    title: string;
    slug: string;
    event_date: string | null;
    photo_count: number;
    cover_url: string | null;
};

export type PublicGalleryDetail = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    event_date: string | null;
    location: string | null;
    photo_count: number;
    cover_url: string | null;
    photos: PublicGalleryPhoto[];
};

export type PublicGalleryFilters = {
    tahun: string;
};
