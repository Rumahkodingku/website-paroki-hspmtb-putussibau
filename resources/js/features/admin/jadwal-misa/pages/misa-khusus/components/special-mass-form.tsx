import { useForm } from '@inertiajs/react';
import { toDateTimeLocalValue } from '@/lib/datetime';
import type { LocationOption } from '../../../types';
import {
    ActiveSwitch,
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '../../../components/form-fields';

export type SpecialMassFormInstance = ReturnType<
    typeof useForm<SpecialMassFormData>
>;

export type SpecialMassFormData = {
    location_id: string;
    title: string;
    description: string;
    start_at: string;
    end_at: string;
    notes: string;
    is_active: boolean;
};

export function emptySpecialMassFormData(): SpecialMassFormData {
    return {
        location_id: '',
        title: '',
        description: '',
        start_at: '',
        end_at: '',
        notes: '',
        is_active: true,
    };
}

type Props = {
    initial: SpecialMassFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    locations: LocationOption[];
    statuses: Record<string, string>;
    submitLabel: string;
    onSubmit: (form: SpecialMassFormInstance) => void;
    onCancel: () => void;
};

export function SpecialMassForm({
    initial,
    labels,
    hints,
    locations,
    statuses,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<SpecialMassFormData>(initial);

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <div className="grid gap-6 md:grid-cols-2">
                <Field
                    id="special-location"
                    label={labels.location_id ?? 'Lokasi'}
                    error={form.errors.location_id}
                >
                    <ChoiceSelect
                        id="special-location"
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
                    id="special-title"
                    label={labels.title ?? 'Judul'}
                    error={form.errors.title}
                >
                    <FormInput
                        id="special-title"
                        value={form.data.title}
                        onChange={(value) => form.setData('title', value)}
                        required
                    />
                </Field>

                <Field
                    id="special-start-at"
                    label={labels.start_at ?? 'Waktu mulai'}
                    hint={hints.start_at}
                    error={form.errors.start_at}
                >
                    <FormInput
                        id="special-start-at"
                        type="datetime-local"
                        value={form.data.start_at}
                        onChange={(value) => form.setData('start_at', value)}
                        required
                    />
                </Field>

                <Field
                    id="special-end-at"
                    label={labels.end_at ?? 'Waktu selesai'}
                    hint={hints.end_at}
                    error={form.errors.end_at}
                >
                    <FormInput
                        id="special-end-at"
                        type="datetime-local"
                        value={form.data.end_at}
                        onChange={(value) => form.setData('end_at', value)}
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="special-description"
                        label={labels.description ?? 'Deskripsi'}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="special-description"
                            rows={3}
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                        />
                    </Field>
                </div>

                <div className="md:col-span-2">
                    <Field
                        id="special-notes"
                        label={labels.notes ?? 'Catatan'}
                        error={form.errors.notes}
                    >
                        <FormTextarea
                            id="special-notes"
                            rows={2}
                            value={form.data.notes}
                            onChange={(value) => form.setData('notes', value)}
                        />
                    </Field>
                </div>
            </div>

            <ActiveSwitch
                id="special-active"
                checked={form.data.is_active}
                onChange={(checked) => form.setData('is_active', checked)}
                activeLabel={statuses.active ?? 'Aktif'}
            />

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}

export function specialMassFormDataFrom(
    row: {
        location_id: number;
        title: string;
        description: string | null;
        start_at: string;
        end_at: string | null;
        notes: string | null;
        is_active: boolean;
    },
    displayTimeZone?: string,
): SpecialMassFormData {
    return {
        location_id: String(row.location_id),
        title: row.title,
        description: row.description ?? '',
        start_at: toDateTimeLocalValue(row.start_at, displayTimeZone),
        end_at: row.end_at
            ? toDateTimeLocalValue(row.end_at, displayTimeZone)
            : '',
        notes: row.notes ?? '',
        is_active: row.is_active,
    };
}
