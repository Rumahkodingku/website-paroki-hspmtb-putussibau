import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import type { ServiceCategoryFormData } from '../../../types/service-category';

export type ServiceCategoryFormInstance = ReturnType<
    typeof useForm<ServiceCategoryFormData>
>;

export function emptyServiceCategoryFormData(): ServiceCategoryFormData {
    return {
        name: '',
        slug: '',
        description: '',
        sort_order: '0',
        is_active: true,
    };
}

type Props = {
    initial: ServiceCategoryFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    submitLabel: string;
    onSubmit: (form: ServiceCategoryFormInstance) => void;
    onCancel: () => void;
};

export function ServiceCategoryForm({
    initial,
    labels,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<ServiceCategoryFormData>(initial);

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
                    id="category-name"
                    label={labels.category_name ?? 'Nama Kategori'}
                    error={form.errors.name}
                    required
                >
                    <FormInput
                        id="category-name"
                        value={form.data.name}
                        onChange={(value) => form.setData('name', value)}
                        placeholder="Misalnya: Sakramen Inisiasi"
                        required
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="category-description"
                        label={labels.category_description ?? 'Deskripsi'}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="category-description"
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
                id="category-active"
                checked={form.data.is_active}
                onChange={(checked) => form.setData('is_active', checked)}
                activeLabel={labels.category_is_active ?? 'Kategori aktif'}
            />

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
