import { useForm } from '@inertiajs/react';
import type { NoticeTargets } from '../../../types';
import {
    ActiveSwitch,
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';

export type ScheduleNoticeFormInstance = ReturnType<
    typeof useForm<ScheduleNoticeFormData>
>;

export type ScheduleNoticeFormData = {
    title: string;
    message: string;
    target: string;
    location_id: string;
    mass_schedule_id: string;
    special_mass_id: string;
    starts_at: string;
    ends_at: string;
    is_active: boolean;
};

export function emptyScheduleNoticeFormData(): ScheduleNoticeFormData {
    return {
        title: '',
        message: '',
        target: '',
        location_id: '',
        mass_schedule_id: '',
        special_mass_id: '',
        starts_at: '',
        ends_at: '',
        is_active: true,
    };
}

function targetOptions(targets: NoticeTargets) {
    return [
        ...targets.locations.map((location) => ({
            value: `location:${location.id}`,
            label: `Lokasi — ${location.name}`,
        })),
        ...targets.mass_schedules.map((schedule) => ({
            value: `schedule:${schedule.id}`,
            label: `Jadwal rutin — ${schedule.name}`,
        })),
        ...targets.special_masses.map((mass) => ({
            value: `special:${mass.id}`,
            label: `Misa khusus — ${mass.title}`,
        })),
    ];
}

export function targetToColumns(target: string) {
    const [kind, id] = target.split(':');

    return {
        location_id: kind === 'location' ? id : '',
        mass_schedule_id: kind === 'schedule' ? id : '',
        special_mass_id: kind === 'special' ? id : '',
    };
}

export function columnsToTarget(notice: {
    location_id: number | null;
    mass_schedule_id: number | null;
    special_mass_id: number | null;
}): string {
    if (notice.mass_schedule_id !== null) {
        return `schedule:${notice.mass_schedule_id}`;
    }

    if (notice.special_mass_id !== null) {
        return `special:${notice.special_mass_id}`;
    }

    if (notice.location_id !== null) {
        return `location:${notice.location_id}`;
    }

    return '';
}

type Props = {
    initial: ScheduleNoticeFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    statuses: Record<string, string>;
    noticeScopes: Record<string, string>;
    targets: NoticeTargets;
    submitLabel: string;
    onSubmit: (form: ScheduleNoticeFormInstance) => void;
    onCancel: () => void;
};

export function ScheduleNoticeForm({
    initial,
    labels,
    hints,
    statuses,
    noticeScopes,
    targets,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<ScheduleNoticeFormData>(initial);

    const generalLabel = noticeScopes.general ?? 'Umum (semua pengunjung)';

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
                <div className="md:col-span-2">
                    <Field
                        id="notice-title"
                        label={labels.title ?? 'Judul'}
                        error={form.errors.title}
                    >
                        <FormInput
                            id="notice-title"
                            value={form.data.title}
                            onChange={(value) => form.setData('title', value)}
                            required
                        />
                    </Field>
                </div>

                <div className="md:col-span-2">
                    <Field
                        id="notice-message"
                        label={labels.message ?? 'Pesan'}
                        error={form.errors.message}
                    >
                        <FormTextarea
                            id="notice-message"
                            rows={3}
                            value={form.data.message}
                            onChange={(value) => form.setData('message', value)}
                        />
                    </Field>
                </div>

                <div className="md:col-span-2">
                    <Field
                        id="notice-target"
                        label="Target"
                        hint={`${hints.note_general ?? ''} ${hints.note_exclusive ?? ''}`.trim()}
                        error={
                            form.errors.location_id ??
                            form.errors.mass_schedule_id ??
                            form.errors.special_mass_id
                        }
                    >
                        <ChoiceSelect
                            id="notice-target"
                            value={form.data.target}
                            onChange={(value) => {
                                form.setData('target', value);
                                // Keeping the three columns in step with the
                                // picker is what stops the form from showing a
                                // combination the server will refuse.
                                const columns = targetToColumns(value);
                                form.setData(
                                    'location_id',
                                    columns.location_id ?? '',
                                );
                                form.setData(
                                    'mass_schedule_id',
                                    columns.mass_schedule_id ?? '',
                                );
                                form.setData(
                                    'special_mass_id',
                                    columns.special_mass_id ?? '',
                                );
                            }}
                            options={[
                                { value: '', label: generalLabel },
                                ...targetOptions(targets),
                            ]}
                        />
                    </Field>
                </div>

                <Field
                    id="notice-starts-at"
                    label={labels.starts_at ?? 'Mulai tampil'}
                    hint={hints.starts_at}
                    error={form.errors.starts_at}
                >
                    <FormInput
                        id="notice-starts-at"
                        type="datetime-local"
                        value={form.data.starts_at}
                        onChange={(value) => form.setData('starts_at', value)}
                        required
                    />
                </Field>

                <Field
                    id="notice-ends-at"
                    label={labels.ends_at ?? 'Akhir tampil'}
                    hint={hints.ends_at}
                    error={form.errors.ends_at}
                >
                    <FormInput
                        id="notice-ends-at"
                        type="datetime-local"
                        value={form.data.ends_at}
                        onChange={(value) => form.setData('ends_at', value)}
                    />
                </Field>
            </div>

            <ActiveSwitch
                id="notice-active"
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
