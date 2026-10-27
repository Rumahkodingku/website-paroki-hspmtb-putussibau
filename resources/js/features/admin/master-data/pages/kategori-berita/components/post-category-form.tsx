import { useForm } from '@inertiajs/react';
import { Field, FormActions, FormInput } from '@/components/form-fields';
import type { FormMeta } from '@/types/news-agenda';
import type { PostCategoryFormData } from '../../../types/post-category';

export type PostCategoryFormInstance = ReturnType<
    typeof useForm<PostCategoryFormData>
>;

export function emptyPostCategoryFormData(): PostCategoryFormData {
    return { name: '', slug: '', sort_order: 0 };
}

type Props = {
    initial: PostCategoryFormData;
    labels: FormMeta['labels'];
    hints: FormMeta['hints'];
    submitLabel: string;
    onSubmit: (form: PostCategoryFormInstance) => void;
    onCancel: () => void;
};

export function PostCategoryForm({
    initial,
    labels,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<PostCategoryFormData>(initial);

    return (
        <form
            // noValidate on purpose. With `required` and no noValidate the
            // browser validates first, never fires submit, and the Indonesian
            // message from lang/id never appears — the server is the only
            // validator. See AGENTS.md.
            noValidate
            onSubmit={(event: any) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <Field
                id="post-category-name"
                label={labels.name ?? 'Nama kategori'}
                error={form.errors.name}
                required
            >
                <FormInput
                    id="post-category-name"
                    value={form.data.name}
                    onChange={(value) => form.setData('name', value)}
                    placeholder="Contoh: Renungan"
                    required
                />
            </Field>

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
