import { useForm } from '@inertiajs/react';
import { Field, FormActions, FormInput } from '@/components/form-fields';
import { MediaUploadField } from '@/components/media-upload-field';
import { RichTextEditor } from '@/components/rich-text-editor';
import type { ParishProfileFormData, ParishProfileRow } from '../types';

export type ProfileFormInstance = ReturnType<
    typeof useForm<ParishProfileFormData>
>;

export function profileToFormData(
    row: ParishProfileRow,
): ParishProfileFormData {
    return {
        description: row.description ?? '',
        established_year:
            row.established_year === null ? '' : String(row.established_year),
        cover_media_id: row.cover_media_id,
        cover_alt: row.cover_alt ?? '',
        video_url: row.video_url ?? '',
    };
}

type Props = {
    initial: ParishProfileFormData;
    currentCoverUrl: string | null;
    labels: Record<string, string>;
    hints: Record<string, string>;
    onSubmit: (form: ProfileFormInstance) => void;
    onCancel: () => void;
};

export function ProfileForm({
    initial,
    currentCoverUrl,
    labels,
    hints,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<ParishProfileFormData>(initial);

    return (
        <form
            noValidate
            onSubmit={(event: any) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <RichTextEditor
                id="profile-description"
                label={labels.description ?? 'Deskripsi lengkap'}
                value={form.data.description}
                onChange={(html) => form.setData('description', html)}
            />

            <Field
                id="profile-established-year"
                label={labels.established_year ?? 'Tahun berdiri'}
                hint={hints.established_year}
                error={form.errors.established_year}
            >
                <FormInput
                    id="profile-established-year"
                    inputMode="numeric"
                    value={form.data.established_year}
                    onChange={(value) =>
                        form.setData('established_year', value)
                    }
                    placeholder="1956"
                />
            </Field>

            <MediaUploadField
                id="profile-cover"
                variant="image"
                aspect={16 / 9}
                label={labels.cover_media_id ?? 'Gambar sampul'}
                hint={hints.cover_media_id}
                altLabel={labels.cover_alt ?? 'Teks alternatif sampul'}
                altValue={form.data.cover_alt}
                onAltChange={(value) => form.setData('cover_alt', value)}
                altError={form.errors.cover_alt}
                emptyHint="Belum ada gambar sampul."
                onMediaChange={(id) => form.setData('cover_media_id', id)}
                currentUrl={currentCoverUrl}
                fieldName="cover_media_id"
                removable
            />

            <Field
                id="profile-video-url"
                label={labels.video_url ?? 'Tautan video'}
                hint={hints.video_url}
                error={form.errors.video_url}
            >
                <FormInput
                    id="profile-video-url"
                    inputMode="url"
                    value={form.data.video_url}
                    onChange={(value) => form.setData('video_url', value)}
                    placeholder="https://www.youtube.com/watch?v=..."
                />
            </Field>

            <FormActions
                processing={form.processing}
                submitLabel="Simpan informasi profil"
                onCancel={onCancel}
            />
        </form>
    );
}
