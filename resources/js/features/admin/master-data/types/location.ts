/*
 * LocationRow — a location as the Master Data list and form need it.
 *
 * Moved out of features/admin/jadwal-misa/types.ts when Lokasi became Master
 * Data (D-48). The Jadwal Misa screens still read `locations` on a mass, a
 * special mass and a notice, but the record itself stopped being theirs: an
 * activity is announced in a room too, and the mass schedule never owned it.
 *
 * The three optional counts are the delete dialog's input, and they are
 * optional for one reason: the list computes them, and a page that wants the
 * dialog without the counts should be able to omit them. Showing 0 for "we did
 * not look" would promise a delete the server is about to refuse.
 *
 * LocationType is not declared here. It has two consumers — this feature and
 * the Jadwal Misa filter dropdown — and ARCHITECTURE.md Part B section 4
 * forbids one feature importing another, so it lives with LocationOption in
 * types/location.ts.
 *
 * @see docs/DECISIONS.md D-48
 */

/** LocationResource */
import type { LocationType } from '@/types/location';

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

/**
 * What the location pages receive that is not a record.
 *
 * Declared here rather than imported from features/admin/jadwal-misa/types.ts,
 * where the shape it mirrors lives. MassScheduleFormMeta is the concern the
 * controller uses and it is still called that, but ARCHITECTURE.md Part B
 * section 4 forbids one feature importing another, and "import the Jadwal Misa
 * type from the Master Data feature" is precisely the coupling that rule exists
 * to prevent. Listing the four fields the pages actually read is also more
 * honest than picking six out of eight.
 *
 * @see docs/DECISIONS.md D-48
 */
export type LocationFormMeta = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    statuses: Record<string, string>;
    /** App\Enums\LocationType, paired with its Indonesian label. */
    types: Array<{ value: LocationType; label: string }>;
};
