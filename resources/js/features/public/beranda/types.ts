import type { ArticleCardData } from '@/components/article-card';
import type { EventCardData } from '@/components/event-card';
import type { NextMassCardData } from '@/components/next-mass-card';

/*
 * The public homepage's page props.
 *
 * Mirrors App\Http\Controllers\Home\PublicHomeController one for one. The card
 * shapes are imported from the shared components rather than copied from a
 * feature's types file: the homepage may not reach into another feature.
 */

export type HomeService = {
    id: number;
    title: string;
    slug: string;
    thumbnail: string | null;
    thumbnail_alt: string | null;
    /** Fallback illustration, not a photograph of the parish (D-71). */
    thumbnail_is_illustration?: boolean;
    thumbnail_srcset?: string;
    thumbnail_sizes?: string;
    summary: string | null;
    is_emergency: boolean;
};

/**
 * One labeled illustration tile. Used only when the gallery has no real photos
 * and the feature is enabled (D-71).
 */
export type HomeIllustration = {
    id: string;
    src: string;
    srcSet: string;
    sizes: string;
    alt: string;
    width: number;
    height: number;
};

export type HomeGalleryPhoto = {
    id: number;
    caption: string | null;
    alt_text: string | null;
    thumb_url: string | null;
    url: string | null;
    width: number | null;
    height: number | null;
    album: {
        title: string;
        slug: string;
    };
};

export type HomeHero = {
    title: string | null;
    description: string | null;
    image: string;
    imageAlt: string;
    ogImage: string;
    width: number;
    height: number;
    motto: string | null;
};

export type HomeSections = {
    devotion: boolean;
    services: boolean;
    gallery: boolean;
};

export type HomepageProps = {
    nextMass: NextMassCardData | null;
    news: ArticleCardData[];
    events: EventCardData[];
    devotion: ArticleCardData | null;
    services: HomeService[];
    galleryPhotos: HomeGalleryPhoto[];
    galleryIllustrations: HomeIllustration[];
    hero: HomeHero;
    sections: HomeSections;
};
