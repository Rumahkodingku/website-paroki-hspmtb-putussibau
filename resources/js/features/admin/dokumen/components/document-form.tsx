import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import { MediaUploadField } from '@/components/media-upload-field';
import { Text } from '@/components/text';
import type {
    DocumentCategoryOption,
    DocumentFormData,
    DocumentFormMeta,
    DocumentStatus,
} from '../types';

export type DocumentFormInstance = ReturnType<typeof useForm<DocumentFormData>>;

type Props = {
    mode: 'create' | 'edit';
    initial: DocumentFormData;
    categories: DocumentCategoryOption[];
    meta: DocumentFormMeta;
    currentFileName?: string | null;
    onSubmit: (form: DocumentFormInstance) => void;
    onCancel: () => void;
};

export function DocumentForm({
    mode,
    initial,
    categories,
    meta,
    currentFileName,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<DocumentFormData>(initial);

    const statusOptions = meta.statuses.map((status) => ({
        value: status.value,
        label: status.label,
    }));

    const submitLabel =
        mode === 'create'
            ? form.data.status === 'published'
                ? (meta.copy.publish ?? 'Terbitkan')
                : (meta.copy.save_draft ?? 'Simpan Draf')
            : (meta.copy.save_changes ?? 'Simpan Perubahan');

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
                    id="document-title"
                    label={meta.labels.title ?? 'Judul dokumen'}
                    hint={meta.hints.title}
                    error={form.errors.title}
                    required
                >
                    <FormInput
                        id="document-title"
                        value={form.data.title}
                        onChange={(value) => form.setData('title', value)}
                        required
                    />
                </Field>

                <Field
                    id="document-category"
                    label={meta.labels.category ?? 'Kategori'}
                    hint={meta.hints.category}
                    error={form.errors.document_category_id}
                    required
                >
                    <ChoiceSelect
                        id="document-category"
                        value={form.data.document_category_id}
                        onChange={(value) =>
                            form.setData('document_category_id', value)
                        }
                        options={categories.map((category) => ({
                            value: String(category.id),
                            label: category.name,
                        }))}
                        placeholder="Pilih kategori"
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="document-description"
                        label={meta.labels.description ?? 'Deskripsi'}
                        hint={meta.hints.description}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="document-description"
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            rows={3}
                        />
                    </Field>
                </div>

                <div className="md:col-span-2">
                    <MediaUploadField
                        variant="document"
                        id="document-file"
                        label={meta.labels.file ?? 'Berkas dokumen'}
                        hint={meta.hints.file}
                        error={form.errors.file}
                        required={mode === 'create'}
                        currentFileName={currentFileName}
                        onFileChange={(file) => form.setData('file', file)}
                    />
                </div>

                <Field
                    id="document-version"
                    label={meta.labels.version ?? 'Versi'}
                    hint={meta.hints.version}
                    error={form.errors.version_label}
                >
                    <FormInput
                        id="document-version"
                        value={form.data.version_label}
                        onChange={(value) =>
                            form.setData('version_label', value)
                        }
                        placeholder="Contoh: 1.0"
                    />
                </Field>

                <Field
                    id="document-status"
                    label={meta.labels.status ?? 'Status publikasi'}
                    hint={meta.hints.status}
                    error={form.errors.status}
                    required
                >
                    <ChoiceSelect
                        id="document-status"
                        value={form.data.status}
                        onChange={(value) =>
                            form.setData('status', value as DocumentStatus)
                        }
                        options={statusOptions}
                    />
                </Field>

                <Field
                    id="document-date"
                    label={meta.labels.document_date ?? 'Tanggal dokumen'}
                    hint={meta.hints.document_date}
                    error={form.errors.document_date}
                >
                    <FormInput
                        id="document-date"
                        type="date"
                        value={form.data.document_date}
                        onChange={(value) =>
                            form.setData('document_date', value)
                        }
                    />
                </Field>

                <Field
                    id="document-expires"
                    label={meta.labels.expires_at ?? 'Berlaku sampai'}
                    hint={meta.hints.expires_at}
                    error={form.errors.expires_at}
                >
                    <FormInput
                        id="document-expires"
                        type="date"
                        value={form.data.expires_at}
                        onChange={(value) => form.setData('expires_at', value)}
                    />
                </Field>
            </div>

            <div className="space-y-3">
                <ActiveSwitch
                    id="document-featured"
                    checked={form.data.is_featured}
                    onChange={(checked) => form.setData('is_featured', checked)}
                    activeLabel={meta.labels.is_featured ?? 'Dokumen unggulan'}
                />

                <Text as="p" variant="body-sm" color="muted">
                    {meta.hints.is_featured}
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
