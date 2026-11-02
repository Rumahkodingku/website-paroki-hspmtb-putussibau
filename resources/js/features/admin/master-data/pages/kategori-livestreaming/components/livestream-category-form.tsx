import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import type { LivestreamCategoryFormData } from '../../../types/livestream-category';

export type LivestreamCategoryFormInstance = ReturnType<
    typeof useForm<LivestreamCategoryFormData>
>;

export function emptyLivestreamCategoryFormData(): LivestreamCategoryFormData {
    return {
        name: '',
        slug: '',
        description: '',
        sort_order: '0',
        is_active: true,
    };
}

type Props = {
    initial: LivestreamCategoryFormData;
    labels?: Record<string, string>;
    hints?: Record<string, string>;
    submitLabel: string;
    onSubmit: (form: LivestreamCategoryFormInstance) => void;
    onCancel: () => void;
};

export function LivestreamCategoryForm({
    initial,
    labels = {},
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<LivestreamCategoryFormData>(initial);

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
                            placeholder="Contoh: Deskripsi kategori"
                        />
                    </Field>
                </div>
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
