import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import type {
    PastorAssignmentFormData,
    PastorAssignmentRow,
} from '../../../types';

export type PastorAssignmentFormInstance = ReturnType<
    typeof useForm<PastorAssignmentFormData>
>;

export function emptyPastorAssignmentFormData(): PastorAssignmentFormData {
    return {
        position: '',
        start_year: '',
        end_year: '',
        is_current: false,
        description: '',
    };
}

export function pastorAssignmentToFormData(
    row: PastorAssignmentRow,
): PastorAssignmentFormData {
    return {
        position: row.position,
        start_year: row.start_year === null ? '' : String(row.start_year),
        end_year: row.end_year === null ? '' : String(row.end_year),
        is_current: row.is_current,
        description: row.description ?? '',
    };
}

type Props = {
    initial: PastorAssignmentFormData;
    positions: Array<{ value: string; label: string }>;
    labels: Record<string, string>;
    onSubmit: (form: PastorAssignmentFormInstance) => void;
    onCancel: () => void;
};

export function PastorAssignmentForm({
    initial,
    positions,
    labels,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<PastorAssignmentFormData>(initial);

    return (
        <form
            noValidate
            onSubmit={(event: any) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <div className="mt-10 grid gap-6 md:grid-cols-2">
                <Field
                    id="assignment-position"
                    label={labels.position ?? 'Jabatan'}
                    error={form.errors.position}
                    required
                >
                    <ChoiceSelect
                        id="assignment-position"
                        value={form.data.position}
                        onChange={(value) => form.setData('position', value)}
                        options={positions}
                        placeholder="Pilih jabatan"
                    />
                </Field>

                <Field
                    id="assignment-start-year"
                    label={labels.start_year ?? 'Tahun mulai'}
                    error={form.errors.start_year}
                >
                    <FormInput
                        id="assignment-start-year"
                        type="number"
                        inputMode="numeric"
                        value={form.data.start_year}
                        onChange={(value) => form.setData('start_year', value)}
                        placeholder="Contoh: 2010"
                    />
                </Field>

                <Field
                    id="assignment-end-year"
                    label={labels.end_year ?? 'Tahun selesai'}
                    error={form.errors.end_year}
                >
                    <FormInput
                        id="assignment-end-year"
                        type="number"
                        inputMode="numeric"
                        value={form.data.end_year}
                        onChange={(value) => form.setData('end_year', value)}
                        placeholder="Kosongkan bila masih menjabat"
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="assignment-description"
                        label={labels.assignment_description ?? 'Keterangan'}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="assignment-description"
                            rows={2}
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            placeholder="Keterangan penugasan (opsional)"
                        />
                    </Field>
                </div>
            </div>

            <ActiveSwitch
                id="assignment-current"
                checked={form.data.is_current}
                onChange={(checked) => form.setData('is_current', checked)}
                activeLabel={labels.is_current ?? 'Sedang bertugas'}
            />

            <FormActions
                processing={form.processing}
                submitLabel="Simpan penugasan"
                onCancel={onCancel}
            />
        </form>
    );
}
