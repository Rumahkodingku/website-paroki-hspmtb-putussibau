import type { Auth } from '@/types';

/** One editable static page in the SEO screen. */
export type PageSeoEntry = {
    key: string;
    label: string;
    url: string;
    meta_title: string | null;
    meta_description: string | null;
    og_media_id: string | null;
    og_image_url: string | null;
};

export type PageSeoProps = {
    pages: PageSeoEntry[];
    auth: Auth;
};
