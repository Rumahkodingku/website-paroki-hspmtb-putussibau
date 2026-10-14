import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as categoriesIndex,
    update as updateCategory,
} from '@/routes/service-categories';
import {
    ServiceCategoryForm,
    type ServiceCategoryFormInstance,
} from './components/service-category-form';
import type { ServiceCategoryRow } from '../../types/service-category';

type Props = {
    category: ServiceCategoryRow;
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Edit({ category, labels, hints }: Props) {
    const onSubmit = (form: ServiceCategoryFormInstance) =>
        form.submit(updateCategory(category.id));

    return (
        <>
            <Head title={`Edit ${category.name}`} />

            <div className="p-6">
                <Heading
                    title="Edit Kategori"
                    description={`Perbarui kategori ${category.name}.`}
                />

                <ServiceCategoryForm
                    initial={{
                        name: category.name,
                        slug: category.slug,
                        description: category.description ?? '',
                        sort_order: String(category.sort_order),
                        is_active: category.is_active,
                    }}
                    labels={labels}
                    hints={hints}
                    submitLabel="Simpan perubahan"
                    onSubmit={onSubmit}
                    onCancel={() => router.visit(categoriesIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Master Data' },
        { title: 'Kategori Pelayanan', href: categoriesIndex() },
        { title: 'Edit' },
    ],
};
