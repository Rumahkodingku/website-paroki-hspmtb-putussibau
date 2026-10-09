/*
 * Types for the public /jadwal-misa page.
 *
 * Mirrors the five resources in app/Http/Resources/Public/MassSchedule. They
 * are separate classes from the admin ones on purpose: the admin resources carry
 * is_active, notes and per-row can flags, and a visitor's page source should not
 * contain any of them (ARCHITECTURE.md Part C rule 2).
 *
 * The three lists arrive as plain arrays rather than as {"data": [...]}, because
 * a resource collection's envelope exists to carry pagination links and this page
 * has no pagination.
 */

/** App\Enums\LocationType */
export type LocationType = 'gereja_paroki' | 'stasi' | 'kapel' | 'lainnya';

/** App\Enums\MassKind */
export type MassKind = 'rutin' | 'khusus';

/** 0 = Minggu, matching App\Enums\DayOfWeek and Carbon's dayOfWeek. */
export type DayOfWeek = 0 | 1 | 2 | 3 | 4 | 5 | 6;

/** PublicLocationResource */
export type PublicLocation = {
    id: number;
    name: string;
    type: LocationType;
    address: string | null;
    /**
     * null when there is nothing to point at — no maps_url and no coordinate
     * pair. The button is then not rendered rather than disabled, because a
     * disabled Maps button on a parish's own page reads as a bug.
     */
    mapsUrl: string | null;
};

/** PublicMassScheduleResource */
export type PublicMassSchedule = {
    id: number;
    day_of_week: DayOfWeek;
    /** "07:30" — a wall clock, never a datetime (PRD D-02). */
    start_time: string;
    name: string;
    language: string | null;
    notes: string | null;
    location: PublicLocation;
};

/** PublicSpecialMassResource */
export type PublicSpecialMass = {
    id: number;
    title: string;
    description: string | null;
    /** ISO 8601 with an explicit offset, already in Asia/Pontianak. */
    start_at: string;
    end_at: string | null;
    location: PublicLocation;
};

/** PublicScheduleNoticeResource */
export type PublicScheduleNotice = {
    id: number;
    title: string;
    message: string;
    is_general: boolean;
};

/** NextMassResource */
export type NextMass = {
    kind: MassKind;
    /** ISO 8601 with an offset, already in the display timezone. */
    starts_at: string;
    name: string;
    language: string | null;
    location: PublicLocation;
};

/** The filter the controller read back out of the query string. */
export type ScheduleFilters = {
    location_id: number | null;
    day: DayOfWeek | null;
};

/** Which of the two tabs the page is showing. */
export type ScheduleTab = 'rutin' | 'khusus';
