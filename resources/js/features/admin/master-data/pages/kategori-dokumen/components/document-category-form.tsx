import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import type { DocumentCategoryFormData } from '../../../types/document-category';

export type DocumentCategoryFormInstance = ReturnType<
    typeof useForm<DocumentCategoryFormData>
>;

export function emptyDocumentCategoryFormData(): DocumentCategoryFormData {
    return {
        name: '',
        slug: '',
        description: '',
        sort_order: '0',
        is_active: true,
    };
}

type Props = {
    initial: DocumentCategoryFormData;
    copy: Record<string, string>;
    submitLabel: string;
    onSubmit: (form: DocumentCategoryFormInstance) => void;
    onCancel: () => void;
};

export function DocumentCategoryForm({
    initial,
    copy,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<DocumentCategoryFormData>(initial);

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
                    id="document-category-name"
                    label={copy.name ?? 'Nama kategori'}
                    error={form.errors.name}
                    required
                >
                    <FormInput
                        id="document-category-name"
                        value={form.data.name}
                        onChange={(value) => form.setData('name', value)}
                        required
                    />
                </Field>

                <Field
                    id="document-category-slug"
                    label={copy.slug ?? 'Slug'}
                    hint="Dibuat otomatis dari nama bila dikosongkan."
                    error={form.errors.slug}
                >
                    <FormInput
                        id="document-category-slug"
                        value={form.data.slug}
                        onChange={(value) => form.setData('slug', value)}
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="document-category-description"
                        label={copy.description_field ?? 'Deskripsi'}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="document-category-description"
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            rows={3}
                        />
                    </Field>
                </div>

                <Field
                    id="document-category-order"
                    label={copy.sort_order ?? 'Urutan'}
                    error={form.errors.sort_order}
                >
                    <FormInput
                        id="document-category-order"
                        type="number"
                        inputMode="numeric"
                        min={0}
                        value={form.data.sort_order}
                        onChange={(value) => form.setData('sort_order', value)}
                    />
                </Field>
            </div>

            <ActiveSwitch
                id="document-category-active"
                checked={form.data.is_active}
                onChange={(checked) => form.setData('is_active', checked)}
                activeLabel={copy.is_active ?? 'Kategori aktif'}
            />

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
