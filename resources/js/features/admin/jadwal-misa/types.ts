/*
 * Types for the Jadwal Misa admin screens.
 *
 * They live in the feature rather than in types/ because ARCHITECTURE.md Part B
 * section 1.1 says a type belongs at app level once two features need it, and
 * `types/` exists so that deleting a feature deletes its contract along with it.
 *
 * Every field here has a counterpart in a PHP resource. When one side changes,
 * the other changes in the same commit — that is ARCHITECTURE.md Part C rule 4,
 * and the shape below is that rule made checkable.
 */

/** App\Enums\LocationType */
export type LocationType = 'gereja_paroki' | 'stasi' | 'kapel' | 'lainnya';

/** App\Enums\MassKind */
export type MassKind = 'rutin' | 'khusus';

/** ScheduleNoticeResource::status() */
export type NoticeStatus = 'draft' | 'scheduled' | 'visible' | 'expired';

/**
 * 0 = Minggu, matching App\Enums\DayOfWeek.
 *
 * A number rather than the Indonesian name, so the value that reaches
 * MassScheduleResource is the value in the database. The label is chosen from
 * `days` on the server.
 */
export type DayOfWeek = 0 | 1 | 2 | 3 | 4 | 5 | 6;

/** LocationOptionResource */
export type LocationOption = {
    id: number;
    name: string;
    type: LocationType;
};

/** LocationResource */
export type LocationRow = {
    id: number;
    name: string;
    slug: string;
    type: LocationType;
    address: string | null;
    latitude: string | null;
    longitude: string | null;
    maps_url: string | null;
    notes: string | null;
    is_active: boolean;
    /**
     * Absent when the list did not count them. The MASS-07 dialog needs this
     * number, and showing 0 for "we did not look" would understate the impact.
     */
    active_schedules_count?: number;
    /**
     * What stands in the way of deleting this location. Absent when the list
     * did not count them, same reason as above: the delete dialog states a
     * number the server then enforces, and a 0 that means "we did not look"
     * would promise a delete that is about to be refused.
     */
    mass_schedules_count?: number;
    special_masses_count?: number;
    schedule_notices_count?: number;
    can: { update: boolean; delete: boolean };
};

/** MassScheduleResource */
export type MassScheduleRow = {
    id: number;
    location_id: number;
    day_of_week: DayOfWeek;
    /** "07:30" — a wall clock, never a datetime (PRD D-02). */
    start_time: string;
    name: string;
    notes: string | null;
    is_active: boolean;
    location: LocationOption;
    /** Notices that point here, and would stop being published. */
    schedule_notices_count?: number;
    can: { update: boolean; delete: boolean };
};

/** SpecialMassResource */
export type SpecialMassRow = {
    id: number;
    location_id: number;
    title: string;
    description: string | null;
    start_at: string;
    end_at: string | null;
    notes: string | null;
    is_active: boolean;
    location: LocationOption;
    /** Notices that point here, and would stop being published. */
    schedule_notices_count?: number;
    can: { update: boolean; delete: boolean };
};

/** MassScheduleOptionResource */
export type MassScheduleOption = {
    id: number;
    name: string;
    day_of_week: DayOfWeek;
    start_time: string;
};

/** SpecialMassOptionResource */
export type SpecialMassOption = {
    id: number;
    title: string;
    start_at: string;
};

/** ScheduleNoticeResource */
export type ScheduleNoticeRow = {
    id: number;
    title: string;
    message: string;
    location_id: number | null;
    mass_schedule_id: number | null;
    special_mass_id: number | null;
    starts_at: string;
    ends_at: string | null;
    is_active: boolean;
    is_general: boolean;
    target_kind: MassKind | null;
    status: NoticeStatus;
    location: LocationOption | null;
    mass_schedule: MassScheduleOption | null;
    special_mass: SpecialMassOption | null;
    /**
     * True when this notice names a target that has been deleted. Null on a
     * page that did not count, which is the same distinction as the counts
     * above: unknown is not false.
     */
    target_deleted?: boolean | null;
    can: { update: boolean; delete: boolean };
};

/** What the notice form's target picker offers. */
export type NoticeTargets = {
    locations: LocationOption[];
    mass_schedules: MassScheduleOption[];
    special_masses: SpecialMassOption[];
};

/**
 * The language file's shape, as MassScheduleFormMeta sends it.
 *
 * Labels and hints are keyed by field name, so adding a field to the form and a
 * key to lang/id/mass-schedule.php is all it takes for it to be labelled.
 */
export type FormMeta = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    statuses: Record<string, string>;
    noticeStatuses: Record<string, string>;
    noticeScopes: Record<string, string>;
    days: Array<{ value: DayOfWeek; label: string }>;
    types: Array<{ value: LocationType; label: string }>;
    kinds: Array<{ value: MassKind; label: string }>;
};

/** What the list pages read back out of the URL. */
export type ListFilters = {
    search?: string;
    status?: string;
    location_id?: string;
};

/** A select option with a value, from FormMeta or built locally. */
export type Choice = { value: string; label: string };

/** The four shortcuts on the module summary. */
export type ModuleShortcut = {
    title: string;
    description: string;
    href: string;
    icon: 'location' | 'regular' | 'special' | 'notice';
};
