/*
 * Types only the Agenda public feature needs.
 *
 * The two resources this feature reads are PublicEventResource and
 * PublicEventDetailResource. They are not the same shape — the detail adds a
 * HTML description and the list sends a plain-text summary — so they get two
 * types here rather than one with optional fields, because the summary is not
 * an optional description: it is a different string.
 */

import type { Paginated } from '@/types';

/** A category as the public filter needs it. */
export type PublicEventCategory = {
    id: number;
    name: string;
    slug: string;
};

/** App\Http\Resources\Public\Agenda\PublicEventResource */
export type PublicEvent = {
    id: number;
    title: string;
    slug: string;
    /** Plain text, stripped of the stored HTML. Never rendered as markup. */
    summary: string | null;
    /** ISO 8601 in UTC; React renders it in the shared `displayTimezone`. */
    start_at: string;
    end_at: string | null;
    is_all_day: boolean;
    location_name: string | null;
    location_url: string | null;
    poster: string | null;
    poster_alt: string | null;
    status: 'scheduled' | 'cancelled' | 'postponed';
    /** EventStatus::label() from lang/id/agenda.php, so React never retypes it. */
    status_label: string;
    category: {
        id: number;
        name: string;
        slug: string;
    } | null;
};

/** App\Http\Resources\Public\Agenda\PublicEventDetailResource */
export type PublicEventDetail = PublicEvent & {
    /** Sanitized HTML. The one place on the agenda that renders markup. */
    description: string | null;
    plain_description: string | null;
};

export type AgendaMode = 'daftar' | 'kalender';

export type AgendaFilters = {
    mode: AgendaMode;
    kategori: string;
    bulan: string;
};

export type AgendaIndexProps = {
    mode: AgendaMode;
    upcoming: PublicEvent[];
    monthly: PublicEvent[];
    categories: PublicEventCategory[];
    /** YYYY-MM-DD in the display timezone, computed server-side. */
    today: string;
    filters: AgendaFilters;
};

export type AgendaShowProps = {
    event: PublicEventDetail;
    related: PublicEvent[];
};

export type { Paginated };
