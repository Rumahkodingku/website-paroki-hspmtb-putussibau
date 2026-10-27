import {
    ChoiceSelect,
    Field,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import type { CategoryOption, CommunityFormData, StatusOption } from '../types';
import type { CommunityFormInstance } from './community-form-instance';
import { CommunityFormPanel } from './community-form-panel';

type Props = {
    form: CommunityFormInstance;
    labels: Record<string, string>;
    hints: Record<string, string>;
    statuses: StatusOption[];
    categories: CategoryOption[];
};

export function TabInformasiDasar({
    form,
    labels,
    statuses,
    categories,
}: Props) {
    return (
        <CommunityFormPanel
            title="Informasi Dasar"
            description="Lengkapi informasi umum pelayanan ini, termasuk nama, kategori, dLL."
        >
            <div className="grid gap-6 md:grid-cols-2">
                <Field
                    id="community-name"
                    label={labels.name ?? 'Nama'}
                    error={form.errors.name}
                    required
                >
                    <FormInput
                        id="community-name"
                        value={form.data.name}
                        onChange={(value) => form.setData('name', value)}
                        required
                        placeholder="Contoh: Komunitas muda-mudi"
                    />
                </Field>

                <Field
                    id="community-category"
                    label={labels.category ?? 'Kategori'}
                    error={form.errors.community_category_id}
                    required
                >
                    <ChoiceSelect
                        id="community-category"
                        value={form.data.community_category_id}
                        placeholder="Pilih kategori"
                        options={categories.map((category) => ({
                            value: String(category.id),
                            label: category.name,
                        }))}
                        onChange={(value) =>
                            form.setData('community_category_id', value)
                        }
                    />
                </Field>

                <Field
                    id="community-status"
                    label={labels.status ?? 'Status Komunitas'}
                    error={form.errors.status}
                    required
                >
                    <ChoiceSelect
                        id="community-status"
                        value={form.data.status}
                        options={statuses.map((option) => ({
                            value: option.value,
                            label: option.label,
                        }))}
                        onChange={(value) =>
                            form.setData(
                                'status',
                                value as CommunityFormData['status'],
                            )
                        }
                        placeholder="Pilih status"
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="community-description"
                        label={labels.description ?? 'Deskripsi'}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="community-description"
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            rows={3}
                            placeholder="Contoh: Deskripsi komunitas"
                        />
                    </Field>
                </div>
            </div>
        </CommunityFormPanel>
    );
}
