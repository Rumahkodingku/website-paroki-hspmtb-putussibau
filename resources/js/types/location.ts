/*
 * LocationOption — a location as a dropdown needs it.
 *
 * Two features read it: Jadwal Misa, which owns the locations table, and the
 * Agenda form, which lets an activity point at one. ARCHITECTURE.md Part B
 * section 4 forbids `features/a` importing `features/b`, so the shape two
 * features need lives here rather than being imported across the boundary.
 *
 * The counter-intuitive part is that Jadwal Misa, which owns this table, is the
 * one that may not define the type — the rule is about consumers, not about
 * ownership.
 *
 * LocationType joined it here for the same reason when Phase 05 built the
 * Agenda form (D-48). It has two consumers and no home in either feature.
 *
 * @see resources/js/features/admin/jadwal-misa/types.ts
 */

/** App\Enums\LocationType */
export type LocationType = 'gereja_paroki' | 'stasi' | 'kapel' | 'lainnya';

/** App\Http\Resources\MassSchedule\LocationOptionResource */
export type LocationOption = {
    id: number;
    name: string;
    /** App\Enums\LocationType — kept loose here because nothing renders it. */
    type: string;
};
