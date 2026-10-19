import { Text } from '@/components/text';
import { StatusBadge } from '@/components/list-parts';
import type { LocationRow } from '../../../types/location';

export function LocationCards({
    rows,
    typeLabel,
}: {
    rows: LocationRow[];
    typeLabel: (value: string) => string;
}) {
    if (rows.length === 0) {
        return null;
    }

    return (
        <ul className="space-y-3 md:hidden">
            {rows.map((location) => (
                <li
                    key={location.id}
                    className="rounded-lg border border-border p-4"
                >
                    <div className="flex items-start justify-between gap-3">
                        <div className="space-y-1">
                            <Text as="p" variant="heading-sm">
                                {location.name}
                            </Text>

                            <Text as="p" variant="body-sm" color="muted">
                                {typeLabel(location.type)}
                            </Text>

                            {location.active_schedules_count ? (
                                <Text as="p" variant="body-sm" color="muted">
                                    {location.active_schedules_count} jadwal
                                    aktif
                                </Text>
                            ) : null}
                        </div>

                        <StatusBadge active={location.is_active} />
                    </div>
                </li>
            ))}
        </ul>
    );
}
