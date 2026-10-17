/*
 * Types only the Galeri admin feature needs.
 *
 * Mirrors App\Http\Resources\Galeri\{GalleryAlbumListResource,GalleryAlbumResource,
 * GalleryPhotoResource}. See docs/DECISIONS.md D-65.
 */

export type GalleryAlbumRow = {
    id: number;
    title: string;
    slug: string;
    event_date: string | null;
    location: string | null;
    is_published: boolean;
    published_at: string | null;
    updated_at: string;
    photo_count: number;
    cover_url: string | null;
    can: { update: boolean; delete: boolean };
};

export type GalleryPhotoItem = {
    id: number;
    media_id: number;
    caption: string | null;
    alt_text: string | null;
    sort_order: number;
    url: string | null;
    thumb_url: string | null;
    large_url: string | null;
    width: number | null;
    height: number | null;
};

export type GalleryAlbumDetail = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    event_date: string | null;
    location: string | null;
    cover_photo_id: number | null;
    cover_url: string | null;
    is_published: boolean;
    published_at: string | null;
    updated_at: string;
    photo_count: number;
    photos: GalleryPhotoItem[];
    can: { update: boolean; delete: boolean };
};

export type GalleryAlbumFormData = {
    title: string;
    slug: string;
    description: string;
    event_date: string;
    location: string;
    is_published: boolean;
};
