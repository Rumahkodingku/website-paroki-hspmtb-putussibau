import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import { DatePicker } from '@/components/date-picker';
import { Text } from '@/components/text';
import type { GalleryAlbumFormData } from '../types';
import type { GalleryAlbumFormInstance } from './gallery-form-instance';

export function emptyGalleryFormData(): GalleryAlbumFormData {
    return {
        title: '',
        slug: '',
        description: '',
        event_date: '',
        location: '',
        is_published: false,
    };
}

type Props = {
    initial: GalleryAlbumFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
    submitLabel: string;
    onSubmit: (form: GalleryAlbumFormInstance) => void;
    onCancel: () => void;
};

export function GalleryForm({
    initial,
    labels,
    hints,
    copy,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<GalleryAlbumFormData>(initial);

    return (
        <form
            noValidate
            onSubmit={(event: any) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-8"
        >
            <div className="grid gap-6 md:grid-cols-2">
                <Field
                    id="gallery-title"
                    label={labels.title ?? 'Judul Album'}
                    error={form.errors.title}
                    required
                >
                    <FormInput
                        id="gallery-title"
                        value={form.data.title}
                        onChange={(value) => form.setData('title', value)}
                        required
                    />
                </Field>

                <Field
                    id="gallery-slug"
                    label={labels.slug ?? 'Slug'}
                    hint={hints.slug}
                    error={form.errors.slug}
                    required
                >
                    <FormInput
                        id="gallery-slug"
                        value={form.data.slug}
                        onChange={(value) => form.setData('slug', value)}
                        required
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="gallery-description"
                        label={labels.description ?? 'Deskripsi'}
                        hint={hints.description}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="gallery-description"
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            rows={3}
                        />
                    </Field>
                </div>

                <Field
                    id="gallery-event-date"
                    label={labels.event_date ?? 'Tanggal Kegiatan'}
                    hint={hints.event_date}
                    error={form.errors.event_date}
                >
                    <DatePicker
                        id="gallery-event-date"
                        value={form.data.event_date}
                        onChange={(value) => form.setData('event_date', value)}
                    />
                </Field>

                <Field
                    id="gallery-location"
                    label={labels.location ?? 'Lokasi'}
                    hint={hints.location}
                    error={form.errors.location}
                >
                    <FormInput
                        id="gallery-location"
                        value={form.data.location}
                        onChange={(value) => form.setData('location', value)}
                    />
                </Field>
            </div>

            <div className="space-y-3">
                <ActiveSwitch
                    id="gallery-published"
                    checked={form.data.is_published}
                    onChange={(checked) =>
                        form.setData('is_published', checked)
                    }
                    activeLabel={labels.is_published ?? 'Terbitkan album'}
                />

                <Text as="p" variant="body-sm" color="muted">
                    {form.data.is_published
                        ? 'Album akan tampil di halaman publik galeri.'
                        : (copy.draft_hint ??
                          'Album draf tidak tampil di halaman publik.')}
                </Text>
            </div>

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
