import { useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { DateTimePicker } from '@/components/date-time-picker';
import { MediaUploadField } from '@/components/media-upload-field';
import { RichTextEditor } from '@/components/rich-text-editor';
import {
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
} from '@/components/form-fields';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Text } from '@/components/text';
import { toDateTimeLocalValue } from '@/lib/datetime';
import type { LocationOption } from '@/types/location';
import type { Option } from '@/types/news-agenda';
import type { EventFormData, EventFormMeta } from '../types';

export type EventFormInstance = ReturnType<typeof useForm<EventFormData>>;

export function emptyEventFormData(
    categories: Option[],
    locations: LocationOption[],
): EventFormData {
    return {
        title: '',
        slug: '',
        event_category_id: categories[0]?.id ?? null,
        description: '',
        // Today at 09:00 rather than an empty field: EVT-02 wants a start, and
        // a blank datetime-local is a form that opens with the admin already
        // hunting for a date picker. The parish fills it in.
        start_at: '',
        end_at: '',
        is_all_day: false,
        location_id: locations[0]?.id ?? null,
        location_name: '',
        poster_alt: '',
        status: 'scheduled',
        is_published: true,
        media_id: null,
    };
}

/**
 * What each status actually does, in one sentence.
 *
 * Same device as PostForm's STATUS_MEANING. An agenda reader has to be able to
 * tell, before opening the activity, whether the parish still means to hold it:
 * EVT-08 keeps a cancelled activity on the public calendar and marks it, so
 * "Dibatalkan" is a statement the congregation reads and not a lifecycle state
 * the admin tidies away.
 */
const STATUS_MEANING: Record<EventFormData['status'], string> = {
    scheduled: 'Kegiatan berjalan seperti biasa.',
    cancelled: 'Tetap tampil di agenda publik, ditandai dibatalkan.',
    postponed: 'Tampil dengan keterangan waktu akan diumumkan.',
};

type Props = {
    initial: EventFormData;
    labels: EventFormMeta['labels'];
    hints: EventFormMeta['hints'];
    statuses: EventFormMeta['statuses'];
    statusChoices: EventFormMeta['eventStatusChoices'];
    categories: Option[];
    locations: LocationOption[];
    currentPoster: string | null;
    displayTimezone: string;
    submitLabel: string;
    onSubmit: (form: EventFormInstance) => void;
    onCancel: () => void;
};

/*
 * The order is the order an admin fills the form in, not the order of the
 * table: what it is, where it is, when it is, what it looks like, and how it
 * appears. Roadmap 9 lists those as separate blocks and an activity without a
 * time or a place is not an activity.
 */
export function EventForm({
    initial,
    labels,
    hints,
    statuses,
    statusChoices,
    categories,
    locations,
    currentPoster,
    displayTimezone,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<EventFormData>(initial);

    const categoryOptions = useMemo(
        () =>
            categories.map((category) => ({
                value: String(category.id),
                label: category.name,
            })),
        [categories],
    );

    const locationOptions = useMemo(
        () => [
            { value: '', label: '— Lokasi master —' },
            ...locations.map((location) => ({
                value: String(location.id),
                label: location.name,
            })),
        ],
        [locations],
    );

    const categoryValue =
        form.data.event_category_id === null
            ? ''
            : String(form.data.event_category_id);

    const locationValue =
        form.data.location_id === null ? '' : String(form.data.location_id);

    /*
     * Stored in UTC, edited as a WIB wall clock.
     *
     * The start is always sent; the end is optional and stays whatever the admin
     * typed. Sending an empty end through this converter would turn it into
     * "1970-01-01", which passes `nullable` on the server and then reaches the
     * ICS file as an event that ended before it began.
     */
    useEffect(() => {
        if (form.data.start_at !== '') {
            const local = toDateTimeLocalValue(
                form.data.start_at,
                displayTimezone,
            );

            if (local !== '' && local !== form.data.start_at) {
                form.setData('start_at', local);
            }
        }

        if (form.data.end_at !== '') {
            const local = toDateTimeLocalValue(
                form.data.end_at,
                displayTimezone,
            );

            if (local !== '' && local !== form.data.end_at) {
                form.setData('end_at', local);
            }
        }
    }, [form.data.start_at, form.data.end_at, displayTimezone]);

    /*
     * All-day means no clock, so the time inputs go away rather than being
     * greyed out.
     *
     * EVT-03 lets an all-day activity keep a start datetime — the date is the
     * point and MySQL has no DATE type here — but it does not want an end time
     * the congregation would read as "ends at 09:00". The values are left in
     * the payload rather than cleared: an admin who ticks the box by mistake
     * unticks it and finds the times they typed.
     */
    const showTimes = !form.data.is_all_day;

    return (
        <form
            // See AGENTS.md. Without noValidate the browser validates first,
            // never fires submit, and the Indonesian message from lang/id never
            // appears because the server never runs.
            noValidate
            onSubmit={(event: any) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-8"
        >
            <fieldset className="space-y-6">
                <legend className="text-lg font-semibold">
                    Informasi kegiatan
                </legend>

                <Field
                    id="event-title"
                    label={labels.title ?? 'Judul kegiatan'}
                    error={form.errors.title}
                    required
                >
                    <FormInput
                        id="event-title"
                        value={form.data.title}
                        onChange={(value) => form.setData('title', value)}
                        placeholder="Contoh: Synodi Pastoral Keuskupan"
                        required
                    />
                </Field>

                <div className="grid gap-6 md:grid-cols-2">
                    <Field
                        id="event-slug"
                        label={labels.slug ?? 'Slug'}
                        hint={hints.slug}
                        error={form.errors.slug}
                    >
                        <FormInput
                            id="event-slug"
                            value={form.data.slug}
                            onChange={(value) => form.setData('slug', value)}
                            placeholder="dibuat otomatis dari judul"
                        />
                    </Field>

                    <Field
                        id="event-category"
                        label={labels.event_category_id ?? 'Kategori'}
                        error={form.errors.event_category_id}
                        required
                    >
                        <ChoiceSelect
                            id="event-category"
                            value={categoryValue}
                            onChange={(value) =>
                                form.setData(
                                    'event_category_id',
                                    value === '' ? null : Number(value),
                                )
                            }
                            options={categoryOptions}
                        />
                    </Field>
                </div>

                <Field
                    id="event-description"
                    label={labels.description ?? 'Deskripsi'}
                    hint={hints.description}
                    error={form.errors.description}
                >
                    <RichTextEditor
                        id="event-description"
                        value={form.data.description}
                        onChange={(html) => form.setData('description', html)}
                        label="Deskripsi kegiatan"
                    />
                </Field>
            </fieldset>

            <fieldset className="space-y-6">
                <legend className="text-lg font-semibold">
                    Waktu dan tempat
                </legend>

                <Field
                    id="event-start-at"
                    label={labels.start_at ?? 'Waktu mulai'}
                    hint={hints.start_at}
                    error={form.errors.start_at}
                    required
                >
                    <DateTimePicker
                        id="event-start-at"
                        value={form.data.start_at}
                        onChange={(value) => form.setData('start_at', value)}
                    />
                </Field>

                <div className="flex items-start justify-between gap-4 rounded-lg border border-border p-4">
                    <div className="space-y-1">
                        <Label htmlFor="event-all-day">
                            {labels.is_all_day ?? 'Sepanjang hari'}
                        </Label>
                        <Text as="p" variant="body-sm" color="muted">
                            {hints.is_all_day}
                        </Text>
                    </div>
                    <Switch
                        id="event-all-day"
                        checked={form.data.is_all_day}
                        onCheckedChange={(checked) =>
                            form.setData('is_all_day', checked)
                        }
                    />
                </div>
                {form.errors.is_all_day ? (
                    <Text as="p" variant="body-sm" color="danger" role="alert">
                        {form.errors.is_all_day}
                    </Text>
                ) : null}

                {showTimes ? (
                    <Field
                        id="event-end-at"
                        label={labels.end_at ?? 'Waktu selesai'}
                        hint={hints.end_at}
                        error={form.errors.end_at}
                    >
                        <DateTimePicker
                            id="event-end-at"
                            value={form.data.end_at}
                            onChange={(value) => form.setData('end_at', value)}
                        />
                    </Field>
                ) : null}

                <Field
                    id="event-location"
                    label={labels.location_id ?? 'Lokasi'}
                    hint={hints.location_id}
                    error={form.errors.location_id}
                >
                    <ChoiceSelect
                        id="event-location"
                        value={locationValue}
                        onChange={(value) =>
                            form.setData(
                                'location_id',
                                value === '' ? null : Number(value),
                            )
                        }
                        options={locationOptions}
                    />
                </Field>

                <Field
                    id="event-location-name"
                    label={labels.location_name ?? 'Nama lokasi'}
                    hint={hints.location_name}
                    error={form.errors.location_name}
                >
                    <FormInput
                        id="event-location-name"
                        value={form.data.location_name}
                        onChange={(value) =>
                            form.setData('location_name', value)
                        }
                        placeholder="Kosongkan bila memakai lokasi master"
                    />
                </Field>

                <Text as="p" variant="body-sm" color="muted">
                    {form.data.location_id !== null
                        ? 'Lokasi master dipilih, sehingga nama di atas tidak dipakai. Kosongkan baris lokasi master bila nama yang dipakai berbeda.'
                        : 'Tanpa lokasi master, nama yang diketik di atas yang tampil di agenda publik.'}
                </Text>
            </fieldset>

            <fieldset className="space-y-6">
                <legend className="text-lg font-semibold">Poster</legend>

                <MediaUploadField
                    id="event-media"
                    label={labels.poster_path ?? 'Poster kegiatan'}
                    hint={hints.poster_alt}
                    altLabel={labels.poster_alt ?? 'Teks alternatif poster'}
                    altError={form.errors.poster_alt}
                    altValue={form.data.poster_alt}
                    onAltChange={(value) => form.setData('poster_alt', value)}
                    onMediaChange={(id) => form.setData('media_id', id)}
                    currentUrl={currentPoster}
                    fieldName="poster"
                    aspect={4 / 5}
                />
            </fieldset>

            <fieldset className="space-y-6">
                <legend className="text-lg font-semibold">
                    Pengaturan publikasi
                </legend>

                <Field
                    id="event-status"
                    label={labels.status ?? 'Status kegiatan'}
                    hint={`${statuses[form.data.status] ?? ''} — ${STATUS_MEANING[form.data.status]}`}
                    error={form.errors.status}
                    required
                >
                    <ChoiceSelect
                        id="event-status"
                        value={form.data.status}
                        onChange={(value) =>
                            form.setData(
                                'status',
                                value as EventFormData['status'],
                            )
                        }
                        options={statusChoices}
                    />
                </Field>

                <div className="flex items-start justify-between gap-4 rounded-lg border border-border p-4">
                    <div className="space-y-1">
                        <Label htmlFor="event-published">
                            {labels.is_published ?? 'Tampilkan di situs'}
                        </Label>
                        <Text as="p" variant="body-sm" color="muted">
                            Nonaktifkan untuk menyembunyikan kegiatan tanpa
                            menghapusnya.
                        </Text>
                    </div>
                    <Switch
                        id="event-published"
                        checked={form.data.is_published}
                        onCheckedChange={(checked) =>
                            form.setData('is_published', checked)
                        }
                    />
                </div>
                {form.errors.is_published ? (
                    <Text as="p" variant="body-sm" color="danger" role="alert">
                        {form.errors.is_published}
                    </Text>
                ) : null}
            </fieldset>

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
