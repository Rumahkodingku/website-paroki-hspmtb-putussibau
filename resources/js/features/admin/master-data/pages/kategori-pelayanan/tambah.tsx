import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as categoriesIndex,
    store as storeCategory,
} from '@/routes/service-categories';
import {
    emptyServiceCategoryFormData,
    ServiceCategoryForm,
} from './components/service-category-form';

type Props = {
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Create({ labels, hints }: Props) {
    return (
        <>
            <Head title="Tambah Kategori Layanan" />

            <div className="p-6">
                <Heading
                    title="Tambah Kategori"
                    description="Buat kategori baru untuk mengelompokkan layanan."
                />

                <ServiceCategoryForm
                    initial={emptyServiceCategoryFormData()}
                    labels={labels}
                    hints={hints}
                    submitLabel="Simpan kategori"
                    onSubmit={(form) => form.submit(storeCategory())}
                    onCancel={() => router.visit(categoriesIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Master Data' },
        { title: 'Kategori Layanan', href: categoriesIndex() },
        { title: 'Tambah' },
    ],
};
