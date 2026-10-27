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
                        placeholder="Contoh: Dokumen paroki"
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
                            placeholder="Contoh: Deskripsi kategori"
                        />
                    </Field>
                </div>
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
