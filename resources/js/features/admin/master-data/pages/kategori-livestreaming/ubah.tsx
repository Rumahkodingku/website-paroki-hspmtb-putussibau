import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as categoriesIndex,
    update as updateCategory,
} from '@/routes/livestream-categories';
import {
    LivestreamCategoryForm,
    type LivestreamCategoryFormInstance,
} from './components/livestream-category-form';
import type {
    LivestreamCategoryFormData,
    LivestreamCategoryRow,
} from '../../types/livestream-category';

type Props = {
    category: LivestreamCategoryRow;
    labels?: Record<string, string>;
    hints?: Record<string, string>;
};

export default function Edit({ category, labels = {}, hints = {} }: Props) {
    const initial: LivestreamCategoryFormData = {
        name: category.name,
        slug: category.slug,
        description: category.description ?? '',
        sort_order: String(category.sort_order),
        is_active: category.is_active,
    };

    const submit = (form: LivestreamCategoryFormInstance) => {
        form.submit(updateCategory(category.id));
    };

    return (
        <>
            <Head title={`Ubah Kategori ${category.name}`} />

            <div className="p-6">
                <Heading
                    title="Ubah Kategori Komunitas"
                    description={`Perbarui kategori "${category.name}".`}
                />

                <LivestreamCategoryForm
                    initial={initial}
                    labels={labels}
                    hints={hints}
                    submitLabel="Simpan perubahan"
                    onSubmit={submit}
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
        { title: 'Kategori Komunitas', href: categoriesIndex() },
        { title: 'Ubah' },
    ],
};
