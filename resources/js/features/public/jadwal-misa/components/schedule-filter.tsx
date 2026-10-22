import { CalendarDays, MapPin, RotateCcw } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { Text } from '@/components/text';
import type { PublicLocation } from '../types';

type Props = {
    locations: Pick<PublicLocation, 'id' | 'name'>[];
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
    const activeFilterCount =
        Number(locationId !== null) + Number(day !== null);

    return (
        <section
            aria-label="Filter jadwal misa"
            className="print:hidden"
            data-test="schedule-filter"
        >
            <div className="">
                <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                    <div className="flex min-w-0 items-center gap-3">
                        <div className="min-w-0 space-y-0.5">
                            <Text
                                as="h2"
                                className="text-sm font-semibold tracking-tight sm:text-base"
                            >
                                Temukan Jadwal Misa
                            </Text>
                            <p className="text-sm text-muted-foreground">
                                Saring jadwal berdasarkan lokasi dan hari.
                            </p>
                        </div>
                    </div>

                    {hasFilter ? (
                        <Badge
                            variant="secondary"
                            className="gap-1.5 rounded-full bg-primary px-3 py-1 text-white"
                        >
                            {activeFilterCount} filter aktif
                        </Badge>
                    ) : null}
                </div>

                <div className="grid grid-cols-1 items-end gap-4 sm:grid-cols-[minmax(0,1.4fr)_minmax(11rem,0.8fr)_auto]">
                    <div className="min-w-0 space-y-2">
                        <Label
                            htmlFor="filter-location"
                            className="flex items-center gap-2 text-sm font-medium"
                        >
                            <MapPin
                                className="size-4 text-muted-foreground"
                                aria-hidden="true"
                            />
                            Lokasi misa
                        </Label>

                        <Select
                            value={
                                locationId === null ? 'all' : String(locationId)
                            }
                            onValueChange={(value) =>
                                onChange({
                                    location_id:
                                        value === 'all' ? null : Number(value),
                                    day,
                                })
                            }
                        >
                            <SelectTrigger
                                id="filter-location"
                                className="h-11 w-full cursor-pointer rounded-md py-6 shadow-none"
                                aria-label="Filter berdasarkan lokasi"
                            >
                                <SelectValue placeholder="Pilih lokasi" />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem
                                    value="all"
                                    className="cursor-pointer rounded-md py-4"
                                >
                                    Semua lokasi
                                </SelectItem>

                                {locations.map((location) => (
                                    <SelectItem
                                        key={location.id}
                                        value={String(location.id)}
                                        className="cursor-pointer rounded-md py-4"
                                    >
                                        {location.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="min-w-0 space-y-2">
                        <Label
                            htmlFor="filter-day"
                            className="flex items-center gap-2 text-sm font-medium"
                        >
                            <CalendarDays
                                className="size-4 text-muted-foreground"
                                aria-hidden="true"
                            />
                            Hari misa
                        </Label>

                        <Select
                            value={day === null ? 'all' : String(day)}
                            onValueChange={(value) =>
                                onChange({
                                    location_id: locationId,
                                    day: value === 'all' ? null : Number(value),
                                })
                            }
                        >
                            <SelectTrigger
                                id="filter-day"
                                className="h-11 w-full cursor-pointer rounded-md py-6 shadow-none"
                                aria-label="Filter berdasarkan hari"
                            >
                                <SelectValue placeholder="Pilih hari" />
                            </SelectTrigger>

                            <SelectContent>
                                <SelectItem
                                    value="all"
                                    className="cursor-pointer rounded-md py-4"
                                >
                                    Semua hari
                                </SelectItem>

                                {[0, 1, 2, 3, 4, 5, 6].map((value) => (
                                    <SelectItem
                                        key={value}
                                        value={String(value)}
                                        className="cursor-pointer rounded-md py-4"
                                    >
                                        {dayLabels[value]}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {hasFilter ? (
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() =>
                                onChange({
                                    location_id: null,
                                    day: null,
                                })
                            }
                            className="w-full sm:w-auto"
                        >
                            <RotateCcw className="size-4" aria-hidden="true" />
                            Reset filter
                        </Button>
                    ) : null}
                </div>

                {hasFilter ? (
                    <p className="mt-4 border-t border-border pt-3 text-sm leading-relaxed text-muted-foreground">
                        Jadwal sedang difilter. Tautan halaman dapat dibagikan
                        agar orang lain membuka pilihan filter yang sama.
                    </p>
                ) : null}
            </div>
        </section>
    );
}
