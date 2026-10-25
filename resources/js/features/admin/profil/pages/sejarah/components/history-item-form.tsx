import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
} from '@/components/form-fields';
import { DatePicker } from '@/components/date-picker';
import { MediaUploadField } from '@/components/media-upload-field';
import { RichTextEditor } from '@/components/rich-text-editor';
import type {
    ParishHistoryItemFormData,
    ParishHistoryItemRow,
} from '../../../types';

export type HistoryItemFormInstance = ReturnType<
    typeof useForm<ParishHistoryItemFormData>
>;

export function emptyHistoryItemFormData(): ParishHistoryItemFormData {
    return {
        title: '',
        year: '',
        date: '',
        description: '',
        media_id: null,
        image_alt: '',
        sort_order: '0',
        is_published: false,
    };
}

export function historyItemToFormData(
    row: ParishHistoryItemRow,
): ParishHistoryItemFormData {
    return {
        title: row.title,
        year: row.year === null ? '' : String(row.year),
        date: row.date ?? '',
        description: row.description ?? '',
        media_id: row.media_id,
        image_alt: row.image_alt ?? '',
        sort_order: String(row.sort_order),
        is_published: row.is_published,
    };
}

type Props = {
    initial: ParishHistoryItemFormData;
    currentImageUrl: string | null;
    labels: Record<string, string>;
    hints: Record<string, string>;
    onSubmit: (form: HistoryItemFormInstance) => void;
    onCancel: () => void;
};

export function HistoryItemForm({
    initial,
    currentImageUrl,
    labels,
    hints,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<ParishHistoryItemFormData>(initial);

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
                        id="history-title"
                        label={labels.title ?? 'Judul peristiwa'}
                        error={form.errors.title}
                        required
                    >
                        <FormInput
                            id="history-title"
                            value={form.data.title}
                            onChange={(value) => form.setData('title', value)}
                            placeholder="Contoh: Peletakan batu pertama gereja"
                            required
                        />
                    </Field>
                </div>

                <Field
                    id="history-year"
                    label={labels.year ?? 'Tahun'}
                    hint={hints.year}
                    error={form.errors.year}
                >
                    <FormInput
                        id="history-year"
                        type="number"
                        inputMode="numeric"
                        value={form.data.year}
                        onChange={(value) => form.setData('year', value)}
                        placeholder="Contoh: 1956"
                    />
                </Field>

                <Field
                    id="history-date"
                    label={labels.date ?? 'Tanggal'}
                    hint={hints.date}
                    error={form.errors.date}
                >
                    <DatePicker
                        id="history-date"
                        value={form.data.date}
                        onChange={(value) => form.setData('date', value)}
                    />
                </Field>

                <div className="md:col-span-2">
                    <RichTextEditor
                        id="history-description"
                        label={labels.description ?? 'Uraian'}
                        value={form.data.description}
                        onChange={(html) => form.setData('description', html)}
                    />
                </div>

                <div className="md:col-span-2">
                    <MediaUploadField
                        id="history-image"
                        variant="image"
                        aspect={16 / 9}
                        label={labels.media_id ?? 'Gambar'}
                        emptyHint="Belum ada gambar."
                        altValue={form.data.image_alt}
                        onAltChange={(value) =>
                            form.setData('image_alt', value)
                        }
                        altLabel={labels.image_alt ?? 'Teks alternatif gambar'}
                        altError={form.errors.image_alt}
                        onMediaChange={(id) => form.setData('media_id', id)}
                        currentUrl={currentImageUrl}
                        fieldName="media_id"
                        // Nullable and written straight through, so an empty
                        // value really removes the picture.
                        removable
                    />
                </div>

                <Field
                    id="history-sort-order"
                    label={labels.sort_order ?? 'Urutan'}
                    hint={hints.sort_order}
                    error={form.errors.sort_order}
                >
                    <FormInput
                        id="history-sort-order"
                        type="number"
                        inputMode="numeric"
                        value={form.data.sort_order}
                        onChange={(value) => form.setData('sort_order', value)}
                        placeholder="0"
                    />
                </Field>
            </div>

            <ActiveSwitch
                id="history-published"
                checked={form.data.is_published}
                onChange={(checked) => form.setData('is_published', checked)}
                activeLabel={labels.is_published ?? 'Terbitkan peristiwa'}
            />

            <FormActions
                processing={form.processing}
                submitLabel="Simpan peristiwa"
                onCancel={onCancel}
            />
        </form>
    );
}
