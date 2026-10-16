import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import type { CommunityCategoryFormData } from '../../../types/community-category';

export type CommunityCategoryFormInstance = ReturnType<
    typeof useForm<CommunityCategoryFormData>
>;

export function emptyCommunityCategoryFormData(): CommunityCategoryFormData {
    return {
        name: '',
        slug: '',
        description: '',
        sort_order: '0',
        is_active: true,
    };
}

type Props = {
    initial: CommunityCategoryFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    submitLabel: string;
    onSubmit: (form: CommunityCategoryFormInstance) => void;
    onCancel: () => void;
};

export function CommunityCategoryForm({
    initial,
    labels,
    hints,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<CommunityCategoryFormData>(initial);

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <div className="grid gap-6 md:grid-cols-2">
                <Field
                    id="community-category-name"
                    label={labels.name ?? 'Nama Kategori'}
                    error={form.errors.name}
                    required
                >
                    <FormInput
                        id="community-category-name"
                        value={form.data.name}
                        onChange={(value) => form.setData('name', value)}
                        placeholder="Misalnya: Organisasi/Kategorial"
                        required
                    />
                </Field>

                <Field
                    id="community-category-slug"
                    label={labels.slug ?? 'Slug'}
                    hint={hints.slug}
                    error={form.errors.slug}
                    required
                >
                    <FormInput
                        id="community-category-slug"
                        value={form.data.slug}
                        onChange={(value) => form.setData('slug', value)}
                        placeholder="organisasi-kategorial"
                        required
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="community-category-description"
                        label={labels.description ?? 'Deskripsi'}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="community-category-description"
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            rows={2}
                        />
                    </Field>
                </div>

                <Field
                    id="community-category-sort-order"
                    label={labels.sort_order ?? 'Urutan Tampilan'}
                    error={form.errors.sort_order}
                >
                    <FormInput
                        id="community-category-sort-order"
                        type="number"
                        inputMode="numeric"
                        min={0}
                        value={form.data.sort_order}
                        onChange={(value) => form.setData('sort_order', value)}
                    />
                </Field>
            </div>

            <ActiveSwitch
                id="community-category-active"
                checked={form.data.is_active}
                onChange={(checked) => form.setData('is_active', checked)}
                activeLabel={labels.is_active ?? 'Kategori aktif'}
            />

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
