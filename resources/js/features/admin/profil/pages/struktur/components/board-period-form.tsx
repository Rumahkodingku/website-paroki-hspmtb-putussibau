import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import type {
    ParishBoardPeriodFormData,
    ParishBoardPeriodRow,
} from '../../../types';

export type BoardPeriodFormInstance = ReturnType<
    typeof useForm<ParishBoardPeriodFormData>
>;

export function emptyBoardPeriodFormData(): ParishBoardPeriodFormData {
    return {
        name: '',
        start_year: '',
        end_year: '',
        description: '',
        is_active: false,
        is_published: false,
        sort_order: '0',
    };
}

export function boardPeriodToFormData(
    row: ParishBoardPeriodRow,
): ParishBoardPeriodFormData {
    return {
        name: row.name,
        start_year: row.start_year === null ? '' : String(row.start_year),
        end_year: row.end_year === null ? '' : String(row.end_year),
        description: row.description ?? '',
        is_active: row.is_active,
        is_published: row.is_published,
        sort_order: String(row.sort_order),
    };
}

type Props = {
    initial: ParishBoardPeriodFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    onSubmit: (form: BoardPeriodFormInstance) => void;
    onCancel: () => void;
};

export function BoardPeriodForm({
    initial,
    labels,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<ParishBoardPeriodFormData>(initial);

    return (
        <form
            noValidate
            onSubmit={(event: any) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <div className="grid gap-6 md:grid-cols-2">
                <div className="md:col-span-2">
                    <Field
                        id="period-name"
                        label={labels.period_name ?? 'Nama periode'}
                        error={form.errors.name}
                        required
                    >
                        <FormInput
                            id="period-name"
                            value={form.data.name}
                            onChange={(value) => form.setData('name', value)}
                            placeholder="Contoh: Periode 2025–2028"
                            required
                        />
                    </Field>
                </div>

                <Field
                    id="period-start-year"
                    label={labels.start_year ?? 'Tahun mulai'}
                    error={form.errors.start_year}
                >
                    <FormInput
                        id="period-start-year"
                        type="number"
                        inputMode="numeric"
                        value={form.data.start_year}
                        onChange={(value) => form.setData('start_year', value)}
                        placeholder="Contoh: 2025"
                    />
                </Field>

                <Field
                    id="period-end-year"
                    label={labels.end_year ?? 'Tahun selesai'}
                    error={form.errors.end_year}
                >
                    <FormInput
                        id="period-end-year"
                        type="number"
                        inputMode="numeric"
                        value={form.data.end_year}
                        onChange={(value) => form.setData('end_year', value)}
                        placeholder="Contoh: 2028"
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="period-description"
                        label={labels.description ?? 'Keterangan'}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="period-description"
                            rows={2}
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            placeholder="Keterangan periode (opsional)"
                        />
                    </Field>
                </div>
            </div>

            <ActiveSwitch
                id="period-active"
                checked={form.data.is_active}
                onChange={(checked) => form.setData('is_active', checked)}
                activeLabel={labels.is_active ?? 'Jadikan periode aktif'}
            />

            <ActiveSwitch
                id="period-published"
                checked={form.data.is_published}
                onChange={(checked) => form.setData('is_published', checked)}
                activeLabel={labels.is_published ?? 'Terbitkan periode'}
            />

            <FormActions
                processing={form.processing}
                submitLabel="Simpan periode"
                onCancel={onCancel}
            />
        </form>
    );
}
