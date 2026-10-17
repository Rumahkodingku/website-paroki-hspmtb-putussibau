import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
} from '@/components/form-fields';
import { MediaUploadField } from '@/components/media-upload-field';
import { RichTextEditor } from '@/components/rich-text-editor';
import type { ParishServiceAreaFormData, ParishServiceAreaRow } from '../types';

export type ServiceAreaFormInstance = ReturnType<
    typeof useForm<ParishServiceAreaFormData>
>;

export function emptyServiceAreaFormData(): ParishServiceAreaFormData {
    return {
        content: '',
        map_media_id: null,
        map_url: '',
        is_published: false,
    };
}

export function serviceAreaToFormData(
    row: ParishServiceAreaRow,
): ParishServiceAreaFormData {
    return {
        content: row.content ?? '',
        map_media_id: row.map_media_id,
        map_url: row.map_url ?? '',
        is_published: row.is_published,
    };
}

type Props = {
    initial: ParishServiceAreaFormData;
    currentMapUrl: string | null;
    labels: Record<string, string>;
    hints: Record<string, string>;
    onSubmit: (form: ServiceAreaFormInstance) => void;
    onCancel: () => void;
};

export function ServiceAreaForm({
    initial,
    currentMapUrl,
    labels,
    hints,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<ParishServiceAreaFormData>(initial);

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <RichTextEditor
                id="service-area-content"
                label={labels.content ?? 'Narasi wilayah pelayanan'}
                value={form.data.content}
                onChange={(html) => form.setData('content', html)}
            />

            <Field
                id="service-area-map-url"
                label={labels.map_url ?? 'Tautan peta'}
                hint={hints.map_url}
                error={form.errors.map_url}
            >
                <FormInput
                    id="service-area-map-url"
                    inputMode="url"
                    value={form.data.map_url}
                    onChange={(value) => form.setData('map_url', value)}
                    placeholder="https://maps.google.com/..."
                />
            </Field>

            {/*
             * No `aspect`, so nothing is cropped: this is a map, and cutting it
             * to a ratio removes streets from it. No alt props either — the
             * table has no alt column for it, and an empty label used to render
             * a field nobody could fill in.
             */}
            <MediaUploadField
                id="service-area-map"
                variant="image"
                label={labels.map_media_id ?? 'Peta (gambar)'}
                emptyHint="Belum ada gambar peta."
                onMediaChange={(id) => form.setData('map_media_id', id)}
                currentUrl={currentMapUrl}
                fieldName="map_media_id"
                removable
            />

            <ActiveSwitch
                id="service-area-published"
                checked={form.data.is_published}
                onChange={(checked) => form.setData('is_published', checked)}
                activeLabel={labels.is_published ?? 'Terbitkan narasi wilayah'}
            />

            <FormActions
                processing={form.processing}
                submitLabel="Simpan narasi wilayah"
                onCancel={onCancel}
            />
        </form>
    );
}
