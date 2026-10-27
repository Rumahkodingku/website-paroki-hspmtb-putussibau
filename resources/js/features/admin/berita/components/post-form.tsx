import { useEffect, useMemo } from 'react';
import { useForm } from '@inertiajs/react';
import { RichTextEditor } from '@/components/rich-text-editor';
import { MediaUploadField } from '@/components/media-upload-field';
import {
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
    FormLabel,
} from '@/components/form-fields';
import { Switch } from '@/components/ui/switch';
import { Text } from '@/components/text';
import { DateTimePicker } from '@/components/date-time-picker';
import { toDateTimeLocalValue } from '@/lib/datetime';
import type { Option } from '@/types/news-agenda';
import type { PostFormData, PostFormMeta } from '../types';

export type PostFormInstance = ReturnType<typeof useForm<PostFormData>>;

export function emptyPostFormData(categories: Option[]): PostFormData {
    return {
        title: '',
        slug: '',
        post_category_id: categories[0]?.id ?? null,
        author_name: '',
        excerpt: '',
        content: '',
        image_alt: '',
        scripture_reference: '',
        status: 'draft',
        published_at: '',
        is_pinned: false,
        meta_title: '',
        meta_description: '',
        media_id: null,
    };
}

function usesDate(status: PostFormData['status']): boolean {
    // A draft has no date. Showing an empty field the server ignores teaches
    // the admin that the field does nothing.
    return status === 'scheduled' || status === 'published';
}

/**
 * What each status actually does, in one sentence.
 *
 * The dropdown says "Terbit" and "Diarsipkan" and neither word tells an admin
 * that a Terbit article dated tomorrow will not appear yet, or that a Diarsipkan
 * one stops being reachable at its URL. POST-09 makes that true and surprising
 * enough to be worth stating before the admin picks, not after.
 */
const STATUS_MEANING: Record<PostFormData['status'], string> = {
    draft: 'Hanya terlihat oleh admin. Tidak tampil di halaman publik.',
    scheduled: 'Terbit otomatis pada waktu yang dipilih.',
    published: 'Tampil di halaman publik, dan dapat dibaca siapa saja.',
    archived: 'Berhenti tampil di halaman publik, termasuk di tautan lamanya.',
};

type Props = {
    initial: PostFormData;
    labels: PostFormMeta['labels'];
    hints: PostFormMeta['hints'];
    statuses: PostFormMeta['statuses'];
    statusChoices: PostFormMeta['postStatusChoices'];
    categories: Option[];
    currentImage: string | null;
    displayTimezone: string;
    submitLabel: string;
    onSubmit: (form: PostFormInstance) => void;
    onCancel: () => void;
};

/*
 * Six sections, in the order roadmap 4.1 lists them: what it is, what it says,
 * what it looks like, when it appears, how it is found, and — for a Renungan —
 * the verse it sits on.
 *
 * <fieldset> rather than a pile of divs, so the grouping is real rather than
 * a visual convention: an admin who cannot tell which fields belong together
 * fills the SEO box before writing the article and then rewrites it.
 */
export function PostForm({
    initial,
    labels,
    hints,
    statuses,
    statusChoices,
    categories,
    currentImage,
    displayTimezone,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<PostFormData>(initial);

    const categoryOptions = useMemo(
        () =>
            categories.map((category) => ({
                value: String(category.id),
                label: category.name,
            })),
        [categories],
    );

    const categoryValue =
        form.data.post_category_id === null
            ? ''
            : String(form.data.post_category_id);

    /*
     * The date is stored in UTC and edited as a local wall clock.
     *
     * Sending the ISO string through unchanged would post the browser's own
     * offset, so an admin in a different timezone from the parish schedules an
     * article for the wrong evening. The conversion belongs here rather than in
     * the request, because the request cannot know which zone the admin meant.
     */
    useEffect(() => {
        if (!usesDate(form.data.status) || form.data.published_at === '') {
            return;
        }

        const local = toDateTimeLocalValue(
            form.data.published_at,
            displayTimezone,
        );

        if (local !== '' && local !== form.data.published_at) {
            form.setData('published_at', local);
        }
    }, [form.data.status, form.data.published_at, displayTimezone]);

    const isRenungan =
        categories.find(
            (category) => category.id === form.data.post_category_id,
        )?.slug === 'renungan';

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
                    Informasi utama
                </legend>

                <Field
                    id="post-title"
                    label={labels.title ?? 'Judul artikel'}
                    error={form.errors.title}
                    required
                >
                    <FormInput
                        id="post-title"
                        value={form.data.title}
                        onChange={(value) => form.setData('title', value)}
                        placeholder="Contoh: Mgr. Yohanes mengenangkan Gaudium Vacandi"
                        required
                    />
                </Field>

                <div className="grid gap-6 md:grid-cols-2">
                    <Field
                        id="post-category"
                        label={labels.post_category_id ?? 'Kategori'}
                        error={form.errors.post_category_id}
                        required
                    >
                        <ChoiceSelect
                            id="post-category"
                            value={categoryValue}
                            onChange={(value) =>
                                form.setData(
                                    'post_category_id',
                                    value === '' ? null : Number(value),
                                )
                            }
                            options={categoryOptions}
                            placeholder="Pilih kategori"
                        />
                    </Field>
                </div>

                <Field
                    id="post-author"
                    label={labels.author_name ?? 'Nama penulis'}
                    hint={hints.author_name}
                    error={form.errors.author_name}
                >
                    <FormInput
                        id="post-author"
                        value={form.data.author_name}
                        onChange={(value) => form.setData('author_name', value)}
                        placeholder="Kosongkan untuk memakai nama akun"
                    />
                </Field>

                <Field
                    id="post-excerpt"
                    label={labels.excerpt ?? 'Ringkasan'}
                    hint={hints.excerpt}
                    error={form.errors.excerpt}
                >
                    <FormTextarea
                        id="post-excerpt"
                        rows={3}
                        value={form.data.excerpt}
                        onChange={(value) => form.setData('excerpt', value)}
                        placeholder="Contoh: Ringkasan artikel"
                    />
                </Field>
            </fieldset>

            <fieldset className="space-y-6">
                <legend className="text-lg font-semibold">Isi artikel</legend>

                <Field
                    id="post-content"
                    label={labels.content ?? 'Isi artikel'}
                    error={form.errors.content}
                    required
                >
                    <RichTextEditor
                        id="post-content"
                        value={form.data.content}
                        onChange={(html) => form.setData('content', html)}
                        label="Isi artikel"
                    />
                </Field>
            </fieldset>

            <fieldset className="space-y-6">
                <legend className="text-lg font-semibold">Gambar utama</legend>

                <MediaUploadField
                    id="post-media"
                    label={labels.featured_image ?? 'Gambar utama'}
                    hint={hints.image_alt}
                    altLabel={labels.image_alt ?? 'Teks alternatif gambar'}
                    altError={form.errors.image_alt}
                    altValue={form.data.image_alt}
                    onAltChange={(value) => form.setData('image_alt', value)}
                    onMediaChange={(id) => form.setData('media_id', id)}
                    currentUrl={currentImage}
                    fieldName="featured_image"
                    aspect={16 / 9}
                />
            </fieldset>

            <fieldset className="space-y-6">
                <legend className="text-lg font-semibold">
                    Pengaturan publikasi
                </legend>

                <Field
                    id="post-status"
                    label={labels.status ?? 'Status'}
                    hint={`${statuses[form.data.status] ?? ''} — ${STATUS_MEANING[form.data.status]}`}
                    error={form.errors.status}
                    required
                >
                    <ChoiceSelect
                        id="post-status"
                        value={form.data.status}
                        onChange={(value) =>
                            form.setData(
                                'status',
                                value as PostFormData['status'],
                            )
                        }
                        options={statusChoices}
                        placeholder="Pilih status"
                    />
                </Field>

                {usesDate(form.data.status) ? (
                    <Field
                        id="post-published-at"
                        label={labels.published_at ?? 'Waktu terbit'}
                        hint={hints.published_at}
                        error={form.errors.published_at}
                        required={
                            form.data.status === 'scheduled' ||
                            form.errors.published_at !== undefined
                        }
                    >
                        <DateTimePicker
                            id="post-published-at"
                            value={form.data.published_at}
                            onChange={(value) =>
                                form.setData('published_at', value)
                            }
                        />
                    </Field>
                ) : null}

                <div className="flex items-start justify-between gap-4 rounded-lg border border-border p-4">
                    <div className="space-y-1">
                        <FormLabel htmlFor="post-pinned">
                            {labels.is_pinned ?? 'Sematkan di halaman berita'}
                        </FormLabel>
                        <Text as="p" variant="body-sm" color="muted">
                            Maksimal dua artikel aktif dapat disematkan. Artikel
                            sematan tampil di bagian atas halaman berita.
                        </Text>
                    </div>
                    <Switch
                        id="post-pinned"
                        checked={form.data.is_pinned}
                        onCheckedChange={(checked) =>
                            form.setData('is_pinned', checked)
                        }
                    />
                </div>
                {form.errors.is_pinned ? (
                    <Text as="p" variant="body-sm" color="danger" role="alert">
                        {form.errors.is_pinned}
                    </Text>
                ) : null}
            </fieldset>

            <fieldset className="space-y-6">
                <legend className="text-lg font-semibold">SEO</legend>

                <Field
                    id="post-meta-title"
                    label={labels.meta_title ?? 'Meta title'}
                    hint={hints.meta_title}
                    error={form.errors.meta_title}
                >
                    <FormInput
                        id="post-meta-title"
                        value={form.data.meta_title}
                        onChange={(value) => form.setData('meta_title', value)}
                        placeholder="Contoh: Judul SEO artikel"
                    />
                </Field>

                <Field
                    id="post-meta-description"
                    label={labels.meta_description ?? 'Meta description'}
                    hint={hints.meta_description}
                    error={form.errors.meta_description}
                >
                    <FormTextarea
                        id="post-meta-description"
                        rows={3}
                        value={form.data.meta_description}
                        onChange={(value) =>
                            form.setData('meta_description', value)
                        }
                        placeholder="Contoh: Deskripsi SEO artikel"
                    />
                </Field>
            </fieldset>

            {isRenungan ? (
                <fieldset className="space-y-6">
                    <legend className="text-lg font-semibold">
                        Referensi ayat
                    </legend>

                    <Field
                        id="post-scripture"
                        label={labels.scripture_reference ?? 'Referensi ayat'}
                        hint={hints.scripture_reference}
                        error={form.errors.scripture_reference}
                    >
                        <FormInput
                            id="post-scripture"
                            value={form.data.scripture_reference}
                            onChange={(value) =>
                                form.setData('scripture_reference', value)
                            }
                            placeholder="Contoh: Yohanes 15.13"
                        />
                    </Field>
                </fieldset>
            ) : null}

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
