import type { PublicLivestream } from '@/components/livestream-player';

/*
 * Types the public Livestreaming page needs.
 *
 * The stream shape lives beside the shared player; this file adds only the
 * page-specific filter and option shapes. See docs/DECISIONS.md D-101.
 */

export type { PublicLivestream };

export type LivestreamArchiveFilters = {
    search: string;
    kategori: string;
    tahun: string;
};

export type LivestreamCategoryOption = {
    value: string;
    label: string;
};
