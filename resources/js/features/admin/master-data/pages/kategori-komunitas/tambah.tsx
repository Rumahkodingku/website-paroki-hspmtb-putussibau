import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as categoriesIndex,
    store as storeCategory,
} from '@/routes/community-categories';
import {
    CommunityCategoryForm,
    emptyCommunityCategoryFormData,
} from './components/community-category-form';

type Props = {
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Create({ labels, hints }: Props) {
    return (
        <>
            <Head title="Tambah Kategori Komunitas" />

            <div className="p-6">
                <Heading
                    title="Tambah Kategori Komunitas"
                    description="Buat kategori baru untuk mengelompokkan komunitas."
                />

                <CommunityCategoryForm
                    initial={emptyCommunityCategoryFormData()}
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
        { title: 'Kategori Komunitas', href: categoriesIndex() },
        { title: 'Tambah' },
    ],
};
