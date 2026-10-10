import { useForm } from '@inertiajs/react';
import { formatWibTime } from '@/lib/datetime';
import type { DayOfWeek, LocationOption } from '../types';
import {
    ActiveSwitch,
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from './form-fields';

/*
 * The recurring-mass form, shared by the create and edit pages.
 *
 * The time field is type="time", so the browser hands over "HH:MM" and nothing
 * else has to parse a clock. What is deliberately absent is any timezone: the
 * value is a wall clock (PRD D-02), and the form says so next to the field so
 * nobody later "fixes" it by adding a date.
 */

/** The useForm handle a submit handler receives. */
export type MassScheduleFormInstance = ReturnType<
    typeof useForm<MassScheduleFormData>
>;

export type MassScheduleFormData = {
    location_id: string;
    day_of_week: string;
    start_time: string;
    name: string;
    language: string;
    notes: string;
    is_active: boolean;
};

export function emptyMassScheduleFormData(): MassScheduleFormData {
    return {
        location_id: '',
        day_of_week: '0',
        start_time: '07:30',
        name: '',
        language: '',
        notes: '',
        is_active: true,
    };
}

type Props = {
    initial: MassScheduleFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    days: Array<{ value: DayOfWeek; label: string }>;
    locations: LocationOption[];
    statuses: Record<string, string>;
    submitLabel: string;
    onSubmit: (form: MassScheduleFormInstance) => void;
    onCancel: () => void;
    onDuplicate?: () => void;
};

export function MassScheduleForm({
    initial,
    labels,
    hints,
    days,
    locations,
    statuses,
    submitLabel,
    onSubmit,
    onCancel,
    onDuplicate,
}: Props) {
    const form = useForm<MassScheduleFormData>(initial);

    return (
        <form
            // noValidate: the browser's own bubbles would swallow the submit
            // and the Indonesian messages from the server would never appear.
            // See D-39.
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <div className="grid gap-6 md:grid-cols-2">
                <Field
                    id="schedule-location"
                    label={labels.location_id ?? 'Lokasi'}
                    error={form.errors.location_id}
                >
                    <ChoiceSelect
                        id="schedule-location"
                        value={form.data.location_id}
                        onChange={(value) => form.setData('location_id', value)}
                        options={locations.map((location) => ({
                            value: String(location.id),
                            label: location.name,
                        }))}
                        placeholder="Pilih lokasi"
                    />
                </Field>

                <Field
                    id="schedule-day"
                    label={labels.day_of_week ?? 'Hari'}
                    error={form.errors.day_of_week}
                >
                    <ChoiceSelect
                        id="schedule-day"
                        value={form.data.day_of_week}
                        onChange={(value) => form.setData('day_of_week', value)}
                        options={days.map((day) => ({
                            value: String(day.value),
                            label: day.label,
                        }))}
                    />
                </Field>

                <Field
                    id="schedule-time"
                    label={labels.start_time ?? 'Jam mulai'}
                    hint={hints.start_time}
                    error={form.errors.start_time}
                >
                    <FormInput
                        id="schedule-time"
                        type="time"
                        value={form.data.start_time}
                        onChange={(value) => form.setData('start_time', value)}
                        required
                    />
                </Field>

                <Field
                    id="schedule-name"
                    label={labels.name_mass ?? 'Nama misa'}
                    error={form.errors.name}
                >
                    <FormInput
                        id="schedule-name"
                        value={form.data.name}
                        onChange={(value) => form.setData('name', value)}
                        required
                    />
                </Field>

                <Field
                    id="schedule-language"
                    label={labels.language ?? 'Bahasa'}
                    hint={hints.language}
                    error={form.errors.language}
                >
                    <FormInput
                        id="schedule-language"
                        value={form.data.language}
                        onChange={(value) => form.setData('language', value)}
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="schedule-notes"
                        label={labels.notes ?? 'Catatan'}
                        error={form.errors.notes}
                    >
                        <FormTextarea
                            id="schedule-notes"
                            rows={2}
                            value={form.data.notes}
                            onChange={(value) => form.setData('notes', value)}
                        />
                    </Field>
                </div>
            </div>

            <ActiveSwitch
                id="schedule-active"
                checked={form.data.is_active}
                onChange={(checked) => form.setData('is_active', checked)}
                activeLabel={statuses.active ?? 'Aktif'}
                inactiveLabel={statuses.inactive ?? 'Nonaktif'}
            />

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
                extra={
                    onDuplicate ? (
                        <button
                            type="button"
                            onClick={onDuplicate}
                            className="inline-flex h-11 items-center rounded-md px-2 text-sm text-primary underline-offset-4 hover:text-primary/80 hover:underline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                        >
                            Duplikasi ke lokasi lain
                        </button>
                    ) : null
                }
            />

            {/* What the schedule will look like once saved, so a wall-clock time
                is confirmed in the same format a visitor will read it. */}
            {form.data.start_time ? (
                <p className="text-sm text-muted-foreground">
                    {formatWibTime(form.data.start_time)}
                </p>
            ) : null}
        </form>
    );
}
