import { useForm } from '@inertiajs/react';
import type { UrlMethodPair } from '@inertiajs/core';
import { Radio } from 'lucide-react';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { DateTimePicker } from '@/components/date-time-picker';
import {
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import { Text } from '@/components/text';
import { toIsoWithOffset } from '@/lib/datetime';
import type {
    LivestreamEditorialStatus,
    LivestreamFormData,
    LocationOption,
} from '../types';
import type { LivestreamFormInstance } from './livestream-form-instance';

export function emptyLivestreamFormData(): LivestreamFormData {
    return {
        title: '',
        youtube_url: '',
        description: '',
        livestream_category_id: '',
        location_id: '',
        scheduled_at: '',
        status: 'draft',
        recorded_at: '',
        is_recording_available: false,
    };
}

/**
 * Converts the two datetime-local inputs to ISO instants before submitting.
 *
 * The conversion lives in one exported function rather than in each page so
 * create and edit cannot disagree about the timezone.
 */
export function submitLivestreamForm(
    form: LivestreamFormInstance,
    action: UrlMethodPair,
): void {
    form.transform((data) => ({
        ...data,
        scheduled_at: toIsoWithOffset(data.scheduled_at),
        recorded_at: toIsoWithOffset(data.recorded_at),
    }));
    form.submit(action);
}

type Props = {
    initial: LivestreamFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
    categories: Array<{ value: string; label: string }>;
    statuses: Array<{ value: string; label: string }>;
    locations: LocationOption[];
    submitLabel: string;
    isLive?: boolean;
    onSubmit: (form: LivestreamFormInstance) => void;
    onCancel: () => void;
};

export function LivestreamForm({
    initial,
    labels,
    hints,
    copy,
    categories,
    statuses,
    locations,
    submitLabel,
    isLive = false,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<LivestreamFormData>(initial);

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-8"
        >
            {isLive ? (
                <Alert variant="destructive">
                    <Radio className="size-4" aria-hidden="true" />
                    <AlertTitle>Siaran sedang live</AlertTitle>
                    <AlertDescription>
                        {copy.live_notice ??
                            'Akhiri siaran sebelum mengubah status publikasi.'}
                    </AlertDescription>
                </Alert>
            ) : null}

            <div className="grid gap-6 md:grid-cols-2">
                <div className="md:col-span-2">
                    <Field
                        id="livestream-title"
                        label={labels.title ?? 'Judul Livestreaming'}
                        error={form.errors.title}
                        required
                    >
                        <FormInput
                            id="livestream-title"
                            value={form.data.title}
                            onChange={(value) => form.setData('title', value)}
                            required
                            placeholder="Contoh: Misa Minggu Pagi"
                        />
                    </Field>
                </div>

                <div className="md:col-span-2">
                    <Field
                        id="livestream-youtube-url"
                        label={labels.youtube_url ?? 'URL YouTube'}
                        hint={hints.youtube_url}
                        error={form.errors.youtube_url}
                        required
                    >
                        <FormInput
                            id="livestream-youtube-url"
                            type="url"
                            inputMode="url"
                            value={form.data.youtube_url}
                            onChange={(value) =>
                                form.setData('youtube_url', value)
                            }
                            required
                            placeholder="https://www.youtube.com/watch?v=..."
                        />
                    </Field>
                </div>

                <div className="md:col-span-2">
                    <Field
                        id="livestream-description"
                        label={labels.description ?? 'Deskripsi'}
                        hint={hints.description}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="livestream-description"
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            rows={4}
                            placeholder="Ringkasan singkat siaran atau rekaman."
                        />
                    </Field>
                </div>

                <Field
                    id="livestream-category"
                    label={labels.category ?? 'Kategori'}
                    hint={hints.category}
                    error={form.errors.livestream_category_id}
                >
                    <ChoiceSelect
                        id="livestream-category"
                        value={form.data.livestream_category_id}
                        onChange={(value) =>
                            form.setData('livestream_category_id', value)
                        }
                        options={categories}
                        placeholder="Tanpa kategori"
                    />
                </Field>

                <Field
                    id="livestream-location"
                    label={labels.location ?? 'Lokasi'}
                    hint={hints.location}
                    error={form.errors.location_id}
                >
                    <ChoiceSelect
                        id="livestream-location"
                        value={form.data.location_id}
                        onChange={(value) => form.setData('location_id', value)}
                        options={locations.map((location) => ({
                            value: String(location.id),
                            label: location.name,
                        }))}
                        placeholder="Tanpa lokasi"
                    />
                </Field>

                <Field
                    id="livestream-scheduled-at"
                    label={labels.scheduled_at ?? 'Jadwal Siaran'}
                    hint={hints.scheduled_at}
                    error={form.errors.scheduled_at}
                >
                    <DateTimePicker
                        id="livestream-scheduled-at"
                        value={form.data.scheduled_at}
                        onChange={(value) =>
                            form.setData('scheduled_at', value)
                        }
                    />
                </Field>

                <Field
                    id="livestream-status"
                    label={labels.status ?? 'Status Publikasi'}
                    hint={hints.status}
                    error={form.errors.status}
                    required
                >
                    <ChoiceSelect
                        id="livestream-status"
                        value={form.data.status}
                        onChange={(value) =>
                            form.setData(
                                'status',
                                value as LivestreamEditorialStatus,
                            )
                        }
                        options={statuses}
                        disabled={isLive}
                    />
                </Field>

                <div className="space-y-2 border-t pt-6 md:col-span-2">
                    <div className="flex flex-wrap items-center justify-between gap-4">
                        <div className="space-y-1">
                            <Label htmlFor="livestream-recording-available">
                                {labels.is_recording_available ??
                                    'Rekaman Tersedia'}
                            </Label>
                            <Text as="p" variant="body-sm" color="muted">
                                {hints.is_recording_available}
                            </Text>
                        </div>

                        <Switch
                            id="livestream-recording-available"
                            checked={form.data.is_recording_available}
                            onCheckedChange={(checked) =>
                                form.setData('is_recording_available', checked)
                            }
                        />
                    </div>
                </div>

                <Field
                    id="livestream-recorded-at"
                    label={labels.recorded_at ?? 'Waktu Rekaman'}
                    hint={hints.recorded_at}
                    error={form.errors.recorded_at}
                >
                    <DateTimePicker
                        id="livestream-recorded-at"
                        value={form.data.recorded_at}
                        onChange={(value) => form.setData('recorded_at', value)}
                    />
                </Field>
            </div>

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
