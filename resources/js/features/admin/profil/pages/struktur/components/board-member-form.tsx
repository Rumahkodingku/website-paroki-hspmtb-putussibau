import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import { MediaUploadField } from '@/components/media-upload-field';
import type {
    ParishBoardMemberFormData,
    ParishBoardMemberRow,
} from '../../../types';

export type BoardMemberFormInstance = ReturnType<
    typeof useForm<ParishBoardMemberFormData>
>;

export function emptyBoardMemberFormData(): ParishBoardMemberFormData {
    return {
        name: '',
        position: '',
        photo_media_id: null,
        photo_alt: '',
        description: '',
        sort_order: '0',
        is_published: false,
    };
}

export function boardMemberToFormData(
    row: ParishBoardMemberRow,
): ParishBoardMemberFormData {
    return {
        name: row.name,
        position: row.position,
        photo_media_id: row.photo_media_id,
        photo_alt: row.photo_alt ?? '',
        description: row.description ?? '',
        sort_order: String(row.sort_order),
        is_published: row.is_published,
    };
}

type Props = {
    initial: ParishBoardMemberFormData;
    currentPhotoUrl: string | null;
    labels: Record<string, string>;
    hints: Record<string, string>;
    onSubmit: (form: BoardMemberFormInstance) => void;
    onCancel: () => void;
};

export function BoardMemberForm({
    initial,
    currentPhotoUrl,
    labels,
    hints,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<ParishBoardMemberFormData>(initial);

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
                    id="member-name"
                    label={labels.member_name ?? 'Nama pengurus'}
                    error={form.errors.name}
                    required
                >
                    <FormInput
                        id="member-name"
                        value={form.data.name}
                        onChange={(value) => form.setData('name', value)}
                        placeholder="Nama lengkap pengurus"
                        required
                    />
                </Field>

                <Field
                    id="member-position"
                    label={labels.member_position ?? 'Jabatan'}
                    hint={hints.member_position}
                    error={form.errors.position}
                    required
                >
                    <FormInput
                        id="member-position"
                        value={form.data.position}
                        onChange={(value) => form.setData('position', value)}
                        placeholder="Contoh: Ketua"
                        required
                    />
                </Field>

                <div className="md:col-span-2">
                    <MediaUploadField
                        id="member-photo"
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

                <div className="md:col-span-2">
                    <Field
                        id="member-description"
                        label={labels.assignment_description ?? 'Keterangan'}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="member-description"
                            rows={2}
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            placeholder="Keterangan (opsional)"
                        />
                    </Field>
                </div>

                <Field
                    id="member-sort-order"
                    label={labels.sort_order ?? 'Urutan'}
                    hint={hints.sort_order}
                    error={form.errors.sort_order}
                >
                    <FormInput
                        id="member-sort-order"
                        type="number"
                        inputMode="numeric"
                        value={form.data.sort_order}
                        onChange={(value) => form.setData('sort_order', value)}
                        placeholder="0"
                    />
                </Field>
            </div>

            <ActiveSwitch
                id="member-published"
                checked={form.data.is_published}
                onChange={(checked) => form.setData('is_published', checked)}
                activeLabel={labels.is_published ?? 'Terbitkan anggota'}
            />

            <FormActions
                processing={form.processing}
                submitLabel="Simpan anggota"
                onCancel={onCancel}
            />
        </form>
    );
}
