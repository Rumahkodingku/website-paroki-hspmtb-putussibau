import type { ChangeEvent } from 'react';
import { Field, FormTextarea } from '@/components/form-fields';
import type { ServiceFormInstance } from './service-form-instance';
import Text from '@/components/text';

type Props = {
    form: ServiceFormInstance;
    labels: Record<string, string>;
    hints: Record<string, string>;
    onSubmit: (event: ChangeEvent) => void;
};

export function TabJadwalBiaya({ form, labels, hints, onSubmit }: Props) {
    return (
        <form noValidate onSubmit={onSubmit} className="space-y-6">
            <div className="space-y-1">
                <Text as="h3" variant="h3">
                    Jadwal & Biaya
                </Text>
                <Text as="p" variant="body-sm" color="muted">
                    Lengkapi informasi jadwal dan biaya pelayanan ini. Informasi
                    ini akan ditampilkan di halaman pelayanan.
                </Text>
            </div>

            <div className="space-y-6">
                <Field
                    id="service-schedule"
                    label={labels.schedule_info}
                    error={form.errors.schedule_info}
                >
                    <FormTextarea
                        id="service-schedule"
                        value={form.data.schedule_info}
                        onChange={(value) =>
                            form.setData('schedule_info', value)
                        }
                        rows={4}
                    />
                </Field>

                <Field
                    id="service-fee"
                    label={labels.fee_info}
                    hint={hints.fee_info}
                    error={form.errors.fee_info}
                >
                    <FormTextarea
                        id="service-fee"
                        value={form.data.fee_info}
                        onChange={(value) => form.setData('fee_info', value)}
                        rows={3}
                    />
                </Field>
            </div>
        </form>
    );
}
