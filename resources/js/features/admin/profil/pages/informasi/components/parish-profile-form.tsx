import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import { MediaUploadField } from '@/components/media-upload-field';
import { RichTextEditor } from '@/components/rich-text-editor';
import type { ParishProfileFormData, ParishProfileRow } from '../../../types';

export type ParishProfileFormInstance = ReturnType<
    typeof useForm<ParishProfileFormData>
>;

export function emptyParishProfileFormData(): ParishProfileFormData {
    return {
        name: '',
        short_description: '',
        description: '',
        history_description: '',
        address: '',
        phone: '',
        email: '',
        website: '',
        established_year: '',
        logo_media_id: null,
        logo_alt: '',
        cover_media_id: null,
        cover_alt: '',
        map_url: '',
        video_url: '',
        is_published: false,
    };
}

export function parishProfileToFormData(
    row: ParishProfileRow,
): ParishProfileFormData {
    return {
        name: row.name ?? '',
        short_description: row.short_description ?? '',
        description: row.description ?? '',
        history_description: row.history_description ?? '',
        address: row.address ?? '',
        phone: row.phone ?? '',
        email: row.email ?? '',
        website: row.website ?? '',
        established_year:
            row.established_year === null ? '' : String(row.established_year),
        logo_media_id: row.logo_media_id,
        logo_alt: row.logo_alt ?? '',
        cover_media_id: row.cover_media_id,
        cover_alt: row.cover_alt ?? '',
        map_url: row.map_url ?? '',
        video_url: row.video_url ?? '',
        is_published: row.is_published,
    };
}

type Props = {
    initial: ParishProfileFormData;
    currentLogoUrl: string | null;
    currentCoverUrl: string | null;
    labels: Record<string, string>;
    hints: Record<string, string>;
    onSubmit: (form: ParishProfileFormInstance) => void;
    onCancel: () => void;
};

export function ParishProfileForm({
    initial,
    currentLogoUrl,
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
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-8"
        >
            <fieldset className="space-y-6">
                <legend className="text-body-strong">Identitas</legend>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <Field
                            id="profile-name"
                            label={labels.name ?? 'Nama paroki'}
                            error={form.errors.name}
                            required
                        >
                            <FormInput
                                id="profile-name"
                                value={form.data.name}
                                onChange={(value) =>
                                    form.setData('name', value)
                                }
                                placeholder="Masukkan nama lengkap paroki"
                                required
                            />
                        </Field>
                    </div>

                    <div className="md:col-span-2">
                        <Field
                            id="profile-short-description"
                            label={
                                labels.short_description ?? 'Deskripsi singkat'
                            }
                            hint={hints.short_description}
                            error={form.errors.short_description}
                        >
                            <FormTextarea
                                id="profile-short-description"
                                rows={3}
                                value={form.data.short_description}
                                onChange={(value) =>
                                    form.setData('short_description', value)
                                }
                                placeholder="Satu atau dua kalimat ringkas"
                            />
                        </Field>
                    </div>
                </div>
            </fieldset>

            <fieldset className="space-y-6 border-t pt-8">
                <legend className="text-body-strong">
                    {labels.description ?? 'Deskripsi lengkap'}
                </legend>
                <RichTextEditor
                    id="profile-description"
                    label={labels.description ?? 'Deskripsi lengkap'}
                    value={form.data.description}
                    onChange={(html) => form.setData('description', html)}
                />
            </fieldset>

            <fieldset className="space-y-6 border-t pt-8">
                <legend className="text-body-strong">
                    {labels.history_description ?? 'Narasi sejarah'}
                </legend>
                <RichTextEditor
                    id="profile-history-description"
                    label={labels.history_description ?? 'Narasi sejarah'}
                    value={form.data.history_description}
                    onChange={(html) =>
                        form.setData('history_description', html)
                    }
                />
                <p className="text-caption text-muted-foreground">
                    {hints.history_description}
                </p>
            </fieldset>

            <fieldset className="space-y-6 border-t pt-8">
                <legend className="text-body-strong">
                    Kontak &amp; lokasi
                </legend>

                <div className="grid gap-6 md:grid-cols-2">
                    <div className="md:col-span-2">
                        <Field
                            id="profile-address"
                            label={labels.address ?? 'Alamat'}
                            error={form.errors.address}
                        >
                            <FormTextarea
                                id="profile-address"
                                rows={2}
                                value={form.data.address}
                                onChange={(value) =>
                                    form.setData('address', value)
                                }
                                placeholder="Alamat sekretariat paroki"
                            />
                        </Field>
                    </div>

                    <Field
                        id="profile-phone"
                        label={labels.phone ?? 'Telepon'}
                        error={form.errors.phone}
                    >
                        <FormInput
                            id="profile-phone"
                            value={form.data.phone}
                            onChange={(value) => form.setData('phone', value)}
                            placeholder="Nomor telepon sekretariat"
                        />
                    </Field>

                    <Field
                        id="profile-email"
                        label={labels.email ?? 'Surel'}
                        error={form.errors.email}
                    >
                        <FormInput
                            id="profile-email"
                            type="text"
                            value={form.data.email}
                            onChange={(value) => form.setData('email', value)}
                            placeholder="Alamat surel paroki"
                        />
                    </Field>

                    <Field
                        id="profile-website"
                        label={labels.website ?? 'Situs web'}
                        error={form.errors.website}
                    >
                        <FormInput
                            id="profile-website"
                            inputMode="url"
                            value={form.data.website}
                            onChange={(value) => form.setData('website', value)}
                            placeholder="https://"
                        />
                    </Field>

                    <Field
                        id="profile-established-year"
                        label={labels.established_year ?? 'Tahun berdiri'}
                        hint={hints.established_year}
                        error={form.errors.established_year}
                    >
                        <FormInput
                            id="profile-established-year"
                            type="number"
                            inputMode="numeric"
                            value={form.data.established_year}
                            onChange={(value) =>
                                form.setData('established_year', value)
                            }
                            placeholder="Contoh: 1956"
                        />
                    </Field>

                    <Field
                        id="profile-map-url"
                        label={labels.map_url ?? 'Tautan peta'}
                        hint={hints.map_url}
                        error={form.errors.map_url}
                    >
                        <FormInput
                            id="profile-map-url"
                            inputMode="url"
                            value={form.data.map_url}
                            onChange={(value) => form.setData('map_url', value)}
                            placeholder="https://maps.google.com/..."
                        />
                    </Field>

                    <Field
                        id="profile-video-url"
                        label={labels.video_url ?? 'Tautan video'}
                        error={form.errors.video_url}
                    >
                        <FormInput
                            id="profile-video-url"
                            inputMode="url"
                            value={form.data.video_url}
                            onChange={(value) =>
                                form.setData('video_url', value)
                            }
                            placeholder="https://youtube.com/..."
                        />
                    </Field>
                </div>
            </fieldset>

            <fieldset className="space-y-6 border-t pt-8">
                <legend className="text-body-strong">Gambar</legend>

                <div className="grid gap-8 md:grid-cols-2">
                    <MediaUploadField
                        id="profile-logo"
                        variant="logo"
                        label={labels.logo_media_id ?? 'Logo'}
                        name={form.data.name}
                        emptyHint="Belum ada logo."
                        altValue={form.data.logo_alt}
                        onAltChange={(value) => form.setData('logo_alt', value)}
                        altLabel={labels.logo_alt ?? 'Teks alternatif logo'}
                        altError={form.errors.logo_alt}
                        onMediaChange={(id) =>
                            form.setData('logo_media_id', id)
                        }
                        currentUrl={currentLogoUrl}
                        fieldName="logo_media_id"
                        // Nullable and written straight through, so an empty
                        // value really removes the logo.
                        removable
                    />

                    <MediaUploadField
                        id="profile-cover"
                        variant="image"
                        aspect={16 / 9}
                        label={labels.cover_media_id ?? 'Gambar sampul'}
                        emptyHint="Belum ada gambar sampul."
                        altValue={form.data.cover_alt}
                        onAltChange={(value) =>
                            form.setData('cover_alt', value)
                        }
                        altLabel={labels.cover_alt ?? 'Teks alternatif sampul'}
                        altError={form.errors.cover_alt}
                        onMediaChange={(id) =>
                            form.setData('cover_media_id', id)
                        }
                        currentUrl={currentCoverUrl}
                        fieldName="cover_media_id"
                        removable
                    />
                </div>
            </fieldset>

            <ActiveSwitch
                id="profile-published"
                checked={form.data.is_published}
                onChange={(checked) => form.setData('is_published', checked)}
                activeLabel={labels.is_published ?? 'Terbitkan profil'}
            />

            <FormActions
                processing={form.processing}
                submitLabel="Simpan informasi profil"
                onCancel={onCancel}
            />
        </form>
    );
}
