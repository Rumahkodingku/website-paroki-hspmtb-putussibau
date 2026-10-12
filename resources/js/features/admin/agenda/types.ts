/*
 * Types only the Agenda admin feature needs.
 *
 * The shapes four features share live in types/news-agenda.ts.
 */

import type { FormMeta, Option } from '@/types/news-agenda';
import type { LocationOption } from '@/types/location';

/** App\Enums\EventStatus */
export type EventStatus = 'scheduled' | 'cancelled' | 'postponed';

/** App\Http\Resources\Agenda\EventResource — one row of the admin list. */
export type EventRow = {
    id: number;
    title: string;
    slug: string;
    event_category_id: number;
    description: string | null;
    /** ISO 8601 in UTC; React renders it in the shared `displayTimezone`. */
    start_at: string;
    end_at: string | null;
    is_all_day: boolean;
    location_id: number | null;
    /** Resolved server-side: the master location wins over the free text (EVT-06). */
    location_name: string | null;
    poster_path: string | null;
    /** poster_path resolved to a browser URL, for the picker's preview. */
    poster_url: string | null;
    poster_alt: string | null;
    status: EventStatus;
    is_published: boolean;
    media_id: number | null;
    category: Option | null;
    location: LocationOption | null;
    can: { update: boolean; delete: boolean; duplicate: boolean };
};

/** What the agenda list reads back out of the URL. */
export type EventFilters = {
    search: string;
    status: string;
    kategori: string;
    bulan: string;
    publikasi: string;
    sort: string;
    direction: string;
};

/**
 * What the agenda form holds and posts.
 *
 * start_at and end_at are `datetime-local` wall clocks with no offset, exactly
 * as the browser sends them. The conversion to UTC is the server's job, in
 * prepareForValidation() — see EventValidationRules.
 */
export type EventFormData = {
    title: string;
    slug: string;
    event_category_id: number | null;
    description: string;
    start_at: string;
    end_at: string;
    is_all_day: boolean;
    location_id: number | null;
    location_name: string;
    poster_alt: string;
    status: EventStatus;
    is_published: boolean;
    media_id: number | null;
};

export type EventFormMeta = Pick<FormMeta, 'labels' | 'hints' | 'statuses'> & {
    eventStatusChoices: Array<{ value: EventStatus; label: string }>;
};
