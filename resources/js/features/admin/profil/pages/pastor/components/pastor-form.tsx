import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
} from '@/components/form-fields';
import { MediaUploadField } from '@/components/media-upload-field';
import { RichTextEditor } from '@/components/rich-text-editor';
import type { PastorFormData, PastorRow } from '../../../types';

export type PastorFormInstance = ReturnType<typeof useForm<PastorFormData>>;

export function emptyPastorFormData(): PastorFormData {
    return {
        name: '',
        title: '',
        photo_media_id: null,
        photo_alt: '',
        bio: '',
        sort_order: '0',
        is_published: false,
    };
}

export function pastorToFormData(row: PastorRow): PastorFormData {
    return {
        name: row.name,
        title: row.title ?? '',
        photo_media_id: row.photo_media_id,
        photo_alt: row.photo_alt ?? '',
        bio: row.bio ?? '',
        sort_order: String(row.sort_order),
        is_published: row.is_published,
    };
}

type Props = {
    initial: PastorFormData;
    currentPhotoUrl: string | null;
    labels: Record<string, string>;
    hints: Record<string, string>;
    onSubmit: (form: PastorFormInstance) => void;
    onCancel: () => void;
};

export function PastorForm({
    initial,
    currentPhotoUrl,
    labels,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<PastorFormData>(initial);

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
                <Field
                    id="pastor-name"
                    label={labels.pastor_name ?? 'Nama pastor'}
                    error={form.errors.name}
                    required
                >
                    <FormInput
                        id="pastor-name"
                        value={form.data.name}
                        onChange={(value) => form.setData('name', value)}
                        placeholder="Nama lengkap pastor"
                        required
                    />
                </Field>

                <Field
                    id="pastor-title"
                    label={labels.pastor_title ?? 'Gelar'}
                    error={form.errors.title}
                >
                    <FormInput
                        id="pastor-title"
                        value={form.data.title}
                        onChange={(value) => form.setData('title', value)}
                        placeholder="Contoh: Pst."
                    />
                </Field>

                <div className="md:col-span-2">
                    <RichTextEditor
                        id="pastor-bio"
                        label={labels.bio ?? 'Biografi'}
                        value={form.data.bio}
                        onChange={(html) => form.setData('bio', html)}
                    />
                </div>

                <div className="md:col-span-2">
                    <MediaUploadField
                        id="pastor-photo"
                        variant="avatar"
                        label={labels.photo_media_id ?? 'Foto'}
                        altValue={form.data.photo_alt}
                        onAltChange={(value) =>
                            form.setData('photo_alt', value)
                        }
                        altLabel={labels.photo_alt ?? 'Teks alternatif foto'}
                        onMediaChange={(id) =>
                            form.setData('photo_media_id', id)
                        }
                        currentUrl={currentPhotoUrl}
                        fieldName="photo_media_id"
                        // The photo is a nullable column written straight
                        // through, so an empty value really removes it.
                        removable
                    />
                </div>
            </div>

            <ActiveSwitch
                id="pastor-published"
                checked={form.data.is_published}
                onChange={(checked) => form.setData('is_published', checked)}
                activeLabel={labels.is_published ?? 'Terbitkan data pastor'}
            />

            <FormActions
                processing={form.processing}
                submitLabel="Simpan data pastor"
                onCancel={onCancel}
            />
        </form>
    );
}
