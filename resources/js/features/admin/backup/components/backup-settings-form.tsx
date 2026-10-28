import { useForm } from '@inertiajs/react';
import type { ChangeEvent } from 'react';
import { ChoiceSelect, Field } from '@/components/form-fields';
import InputError from '@/components/input-error';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import { update } from '@/routes/backups/settings';
import type { BackupSettings } from '../types';

const DAYS = [
    { value: '0', label: 'Minggu' },
    { value: '1', label: 'Senin' },
    { value: '2', label: 'Selasa' },
    { value: '3', label: 'Rabu' },
    { value: '4', label: 'Kamis' },
    { value: '5', label: 'Jumat' },
    { value: '6', label: 'Sabtu' },
];

function ToggleRow({
    id,
    label,
    description,
    checked,
    onChange,
    error,
}: {
    id: string;
    label: string;
    description: string;
    checked: boolean;
    onChange: (checked: boolean) => void;
    error?: string;
}) {
    return (
        <Card className="gap-0 py-0">
            <CardContent className="flex items-start justify-between gap-4 p-4">
                <div className="space-y-1">
                    <Text as="p" variant="label">
                        {label}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {description}
                    </Text>
                    <InputError message={error} />
                </div>
                <Switch
                    id={id}
                    checked={checked}
                    onCheckedChange={onChange}
                    aria-label={label}
                />
            </CardContent>
        </Card>
    );
}

function NumberField({
    id,
    label,
    value,
    onChange,
    error,
    min = 1,
    max,
    suffix,
}: {
    id: string;
    label: string;
    value: number;
    onChange: (value: number) => void;
    error?: string;
    min?: number;
    max?: number;
    suffix?: string;
}) {
    return (
        <Field id={id} label={label} error={error}>
            <div className="flex items-center gap-2">
                <Input
                    id={id}
                    type="number"
                    min={min}
                    max={max}
                    value={value}
                    onChange={(event: ChangeEvent<HTMLInputElement>) =>
                        onChange(Number(event.target.value))
                    }
                />
                {suffix ? (
                    <Text as="span" variant="body-sm" color="muted">
                        {suffix}
                    </Text>
                ) : null}
            </div>
        </Field>
    );
}

export function BackupSettingsForm({
    settings,
    timezones,
}: {
    settings: BackupSettings;
    timezones: string[];
}) {
    const form = useForm<BackupSettings>(settings);

    const submit = () => {
        form.put(update.url(), { preserveScroll: true });
    };

    return (
        <form
            onSubmit={(event) => {
                event.preventDefault();
                submit();
            }}
            className="space-y-8"
        >
            <section className="space-y-4">
                <Text as="h3" variant="heading-sm">
                    Jadwal otomatis
                </Text>

                <ToggleRow
                    id="schedule-enabled"
                    label="Aktifkan backup otomatis"
                    description="Jadwal hanya berjalan bila scheduler dan worker terdeteksi."
                    checked={form.data.schedule_enabled}
                    onChange={(value) =>
                        form.setData('schedule_enabled', value)
                    }
                    error={form.errors.schedule_enabled}
                />

                <div className="grid gap-4 md:grid-cols-2">
                    <ToggleRow
                        id="database-enabled"
                        label="Backup database harian"
                        description="Dump database saja pada waktu yang ditentukan."
                        checked={form.data.database_enabled}
                        onChange={(value) =>
                            form.setData('database_enabled', value)
                        }
                        error={form.errors.database_enabled}
                    />
                    <ToggleRow
                        id="full-enabled"
                        label="Backup lengkap mingguan"
                        description="Database dan file upload pada hari yang ditentukan."
                        checked={form.data.full_enabled}
                        onChange={(value) =>
                            form.setData('full_enabled', value)
                        }
                        error={form.errors.full_enabled}
                    />
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <Field
                        id="database-time"
                        label="Waktu backup database"
                        error={form.errors.database_time}
                    >
                        <Input
                            id="database-time"
                            type="time"
                            value={form.data.database_time}
                            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                form.setData(
                                    'database_time',
                                    event.target.value,
                                )
                            }
                        />
                    </Field>

                    <Field
                        id="full-day"
                        label="Hari backup lengkap"
                        error={form.errors.full_day}
                    >
                        <ChoiceSelect
                            id="full-day"
                            value={String(form.data.full_day)}
                            onChange={(value) =>
                                form.setData('full_day', Number(value))
                            }
                            options={DAYS}
                        />
                    </Field>

                    <Field
                        id="full-time"
                        label="Waktu backup lengkap"
                        error={form.errors.full_time}
                    >
                        <Input
                            id="full-time"
                            type="time"
                            value={form.data.full_time}
                            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                form.setData('full_time', event.target.value)
                            }
                        />
                    </Field>
                </div>

                <Field
                    id="timezone"
                    label="Zona waktu jadwal"
                    error={form.errors.timezone}
                >
                    <ChoiceSelect
                        id="timezone"
                        value={form.data.timezone}
                        onChange={(value) => form.setData('timezone', value)}
                        options={timezones.map((timezone) => ({
                            value: timezone,
                            label: timezone,
                        }))}
                    />
                </Field>
            </section>

            <Separator />

            <section className="space-y-4">
                <Text as="h3" variant="heading-sm">
                    Retensi
                </Text>

                <div className="grid gap-4 md:grid-cols-2">
                    <NumberField
                        id="retention-daily"
                        label="Retensi backup database"
                        value={form.data.retention_daily_days}
                        onChange={(value) =>
                            form.setData('retention_daily_days', value)
                        }
                        error={form.errors.retention_daily_days}
                        suffix="hari"
                        max={365}
                    />
                    <NumberField
                        id="retention-weekly"
                        label="Retensi backup lengkap"
                        value={form.data.retention_weekly_weeks}
                        onChange={(value) =>
                            form.setData('retention_weekly_weeks', value)
                        }
                        error={form.errors.retention_weekly_weeks}
                        suffix="minggu"
                        max={52}
                    />
                    <NumberField
                        id="retention-invalid"
                        label="Retensi metadata unggahan gagal"
                        value={form.data.retention_invalid_upload_days}
                        onChange={(value) =>
                            form.setData('retention_invalid_upload_days', value)
                        }
                        error={form.errors.retention_invalid_upload_days}
                        suffix="hari"
                        max={90}
                    />
                    <NumberField
                        id="retention-safety"
                        label="Perlindungan safety backup"
                        value={form.data.retention_safety_days}
                        onChange={(value) =>
                            form.setData('retention_safety_days', value)
                        }
                        error={form.errors.retention_safety_days}
                        suffix="hari"
                        max={365}
                    />
                </div>

                <Text as="p" variant="body-sm" color="muted">
                    Backup manual tidak dihapus otomatis. Safety backup
                    dilindungi sampai masa perlindungannya berakhir.
                </Text>
            </section>

            <Separator />

            <section className="space-y-4">
                <Text as="h3" variant="heading-sm">
                    Batas operasi
                </Text>

                <div className="grid gap-4 md:grid-cols-2">
                    <NumberField
                        id="max-upload"
                        label="Batas ukuran unggahan"
                        value={form.data.max_upload_megabytes}
                        onChange={(value) =>
                            form.setData('max_upload_megabytes', value)
                        }
                        error={form.errors.max_upload_megabytes}
                        suffix="MB"
                        max={2048}
                    />
                    <NumberField
                        id="max-expanded"
                        label="Batas ukuran hasil ekstraksi"
                        value={form.data.max_expanded_megabytes}
                        onChange={(value) =>
                            form.setData('max_expanded_megabytes', value)
                        }
                        error={form.errors.max_expanded_megabytes}
                        suffix="MB"
                        max={8192}
                    />
                    <NumberField
                        id="max-files"
                        label="Batas jumlah file"
                        value={form.data.max_files}
                        onChange={(value) => form.setData('max_files', value)}
                        error={form.errors.max_files}
                        max={100000}
                    />
                    <NumberField
                        id="max-ratio"
                        label="Batas rasio kompresi"
                        value={form.data.max_compression_ratio}
                        onChange={(value) =>
                            form.setData('max_compression_ratio', value)
                        }
                        error={form.errors.max_compression_ratio}
                        max={1000}
                    />
                    <NumberField
                        id="low-disk"
                        label="Ambang peringatan disk"
                        value={form.data.low_disk_threshold_megabytes}
                        onChange={(value) =>
                            form.setData('low_disk_threshold_megabytes', value)
                        }
                        error={form.errors.low_disk_threshold_megabytes}
                        suffix="MB"
                        min={0}
                        max={102400}
                    />
                </div>
            </section>

            <Separator />

            <div className="flex items-center justify-end gap-3">
                <Button type="submit" disabled={form.processing}>
                    Simpan pengaturan
                </Button>
            </div>
        </form>
    );
}
