import InputError from '@/components/input-error';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import type {
    OfficeHoursDay,
    OfficeHoursDayKey,
    OfficeHoursWeek,
} from '../types';

/*
 * The weekly office-hours editor.
 *
 * One row per day: the day name, an opening and a closing time, a status badge
 * and a switch. Monday first, matching the stored order so the admin reads the
 * week the way it is saved.
 *
 * Two behaviours are deliberate rather than incidental.
 *
 * A day that is switched off keeps its times. The inputs are disabled, not
 * cleared, so switching a day back on restores what was there instead of making
 * the admin retype it. The stored value keeps the times too, so that holds
 * across a save.
 *
 * An old free-text value is shown as it is, with a button to replace it. The
 * editor never overwrites it on its own: saving another contact setting must not
 * be the thing that discards the parish's existing text.
 */

export const OFFICE_HOURS_DAYS: { key: OfficeHoursDayKey; label: string }[] = [
    { key: 'monday', label: 'Senin' },
    { key: 'tuesday', label: 'Selasa' },
    { key: 'wednesday', label: 'Rabu' },
    { key: 'thursday', label: 'Kamis' },
    { key: 'friday', label: 'Jumat' },
    { key: 'saturday', label: 'Sabtu' },
    { key: 'sunday', label: 'Minggu' },
];

export function emptyOfficeHoursWeek(): OfficeHoursWeek {
    return OFFICE_HOURS_DAYS.reduce((week, { key }) => {
        week[key] = { is_open: false, open_time: null, close_time: null };

        return week;
    }, {} as OfficeHoursWeek);
}

/** A stored value as a full week, filling anything the server did not send. */
function normalizeWeek(value: OfficeHoursWeek | null): OfficeHoursWeek {
    if (value === null) {
        return emptyOfficeHoursWeek();
    }

    return OFFICE_HOURS_DAYS.reduce((week, { key }) => {
        const day = value[key];

        week[key] = {
            is_open: Boolean(day?.is_open),
            open_time: day?.open_time ?? null,
            close_time: day?.close_time ?? null,
        };

        return week;
    }, {} as OfficeHoursWeek);
}

type OfficeHoursEditorProps = {
    id: string;
    /** A week, a legacy free-text string, or nothing yet. */
    value: string | OfficeHoursWeek | null;
    errors?: Record<string, string | undefined>;
    onChange: (week: OfficeHoursWeek) => void;
};

export function OfficeHoursEditor({
    id,
    value,
    errors = {},
    onChange,
}: OfficeHoursEditorProps) {
    /*
     * The generic form turns every null into '' on the way in, so an empty
     * string means "never configured", not "legacy text". Only a non-empty
     * string is the old free-text value.
     */
    if (typeof value === 'string' && value.trim() !== '') {
        return (
            <div className="space-y-3">
                <div className="space-y-2 rounded-lg border border-border bg-muted/40 p-4">
                    <Text as="p" variant="label" color="foreground">
                        Jadwal lama masih berupa teks
                    </Text>
                    <Text
                        as="p"
                        variant="body"
                        color="muted"
                        className="whitespace-pre-line"
                    >
                        {value}
                    </Text>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => onChange(emptyOfficeHoursWeek())}
                    >
                        Ganti dengan jadwal mingguan
                    </Button>
                </div>
            </div>
        );
    }

    const week = normalizeWeek(typeof value === 'string' ? null : value);

    const update = (day: OfficeHoursDayKey, patch: Partial<OfficeHoursDay>) =>
        onChange({ ...week, [day]: { ...week[day], ...patch } });

    const topError = errors['settings.office_hours'];

    return (
        <div className="space-y-3">
            {OFFICE_HOURS_DAYS.map(({ key, label }) => {
                const day = week[key];
                const openId = `${id}-${key}-open`;
                const closeId = `${id}-${key}-close`;
                const openError =
                    errors[`settings.office_hours.${key}.open_time`];
                const closeError =
                    errors[`settings.office_hours.${key}.close_time`];
                const statusError =
                    errors[`settings.office_hours.${key}.is_open`];

                return (
                    <div
                        key={key}
                        className="grid gap-3 rounded-lg border border-border p-4 lg:grid-cols-[7rem_1fr_1fr_12rem] lg:items-center lg:gap-4"
                    >
                        <Text as="p" variant="heading-sm" color="foreground">
                            {label}
                        </Text>

                        <div className="grid gap-1">
                            <Label htmlFor={openId}>Jam Buka</Label>
                            <Input
                                id={openId}
                                type="time"
                                value={day.open_time ?? ''}
                                disabled={!day.is_open}
                                aria-label={`Jam buka ${label}`}
                                onChange={(event) =>
                                    update(key, {
                                        open_time:
                                            event.target.value === ''
                                                ? null
                                                : event.target.value,
                                    })
                                }
                            />
                            <InputError message={openError} />
                        </div>

                        <div className="grid gap-1">
                            <Label htmlFor={closeId}>Jam Tutup</Label>
                            <Input
                                id={closeId}
                                type="time"
                                value={day.close_time ?? ''}
                                disabled={!day.is_open}
                                aria-label={`Jam tutup ${label}`}
                                onChange={(event) =>
                                    update(key, {
                                        close_time:
                                            event.target.value === ''
                                                ? null
                                                : event.target.value,
                                    })
                                }
                            />
                            <InputError message={closeError} />
                        </div>

                        <div className="flex items-center justify-between gap-3 lg:justify-end">
                            <Badge
                                variant={day.is_open ? 'default' : 'secondary'}
                            >
                                {day.is_open ? 'Buka' : 'Tutup'}
                            </Badge>
                            <Switch
                                checked={day.is_open}
                                onCheckedChange={(checked) =>
                                    update(key, { is_open: checked })
                                }
                                aria-label={`Aktifkan pelayanan ${label}`}
                            />
                            <InputError message={statusError} />
                        </div>
                    </div>
                );
            })}

            <InputError message={topError} />
        </div>
    );
}

export default OfficeHoursEditor;
