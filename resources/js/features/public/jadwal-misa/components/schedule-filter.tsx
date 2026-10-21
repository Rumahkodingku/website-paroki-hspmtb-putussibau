import { Text } from '@/components/text';

/*
 * The location and day filter.
 *
 * Both live in the query string rather than in component state, because roadmap
 * 7.2 asks for a filter a visitor can share, and a URL is the only version of
 * that which survives a reload and works in a printed page.
 *
 * Native selects again, for the reason the admin forms use them: a filter is a
 * control that has to work before JavaScript does, and it is the first thing a
 * visitor on a phone will touch.
 *
 * The two are applied together, never one at a time — roadmap 7.2 asks that they
 * can be used in combination and that a filter which is still valid is not
 * discarded by changing the other.
 */

type Props = {
    locations: Array<{ id: number; name: string }>;
    /** 0 = Minggu. */
    dayLabels: Record<number, string>;
    locationId: number | null;
    day: number | null;
    onChange: (next: {
        location_id: number | null;
        day: number | null;
    }) => void;
};

export function ScheduleFilter({
    locations,
    dayLabels,
    locationId,
    day,
    onChange,
}: Props) {
    const hasFilter = locationId !== null || day !== null;

    return (
        <div
            className="flex flex-wrap items-end gap-3 rounded-lg border border-border p-4 print:hidden"
            data-test="schedule-filter"
        >
            <div className="space-y-2">
                <label
                    htmlFor="filter-location"
                    className="text-sm font-medium"
                >
                    Lokasi
                </label>
                <select
                    id="filter-location"
                    value={locationId ?? ''}
                    onChange={(event: any) =>
                        onChange({
                            location_id: event.target.value
                                ? Number(event.target.value)
                                : null,
                            day,
                        })
                    }
                    className="h-11 w-full min-w-44 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
                >
                    <option value="">Semua lokasi</option>
                    {locations.map((location) => (
                        <option key={location.id} value={String(location.id)}>
                            {location.name}
                        </option>
                    ))}
                </select>
            </div>

            <div className="space-y-2">
                <label htmlFor="filter-day" className="text-sm font-medium">
                    Hari
                </label>
                <select
                    id="filter-day"
                    value={day ?? ''}
                    onChange={(event: any) =>
                        onChange({
                            location_id: locationId,
                            day: event.target.value
                                ? Number(event.target.value)
                                : null,
                        })
                    }
                    className="h-11 w-full min-w-36 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
                >
                    <option value="">Semua hari</option>
                    {[0, 1, 2, 3, 4, 5, 6].map((value) => (
                        <option key={value} value={String(value)}>
                            {dayLabels[value]}
                        </option>
                    ))}
                </select>
            </div>

            {hasFilter ? (
                <button
                    type="button"
                    onClick={() => onChange({ location_id: null, day: null })}
                    className="h-11 rounded-md px-3 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    Reset filter
                </button>
            ) : null}

            {hasFilter ? (
                <Text as="p" variant="body-sm" color="muted" className="w-full">
                    Filter aktif. Tautan di bilah alamat bisa dibagikan.
                </Text>
            ) : null}
        </div>
    );
}
