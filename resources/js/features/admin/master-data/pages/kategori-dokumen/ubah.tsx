import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as categoriesIndex,
    update as updateCategory,
} from '@/routes/document-categories';
import type { DocumentCategoryRow } from '../../types/document-category';
import {
    DocumentCategoryForm,
    type DocumentCategoryFormInstance,
} from './components/document-category-form';

type Props = {
    category: DocumentCategoryRow;
    copy: Record<string, string>;
};

export default function Edit({ category, copy }: Props) {
    return (
        <>
            <Head title={copy.edit_title ?? 'Ubah Kategori Dokumen'} />

            <div className="p-6">
                <Heading
                    title={copy.edit_title ?? 'Ubah Kategori Dokumen'}
                    description={`Lengkapi form untuk kategori ${category.name}.`}
                />

                <DocumentCategoryForm
                    initial={{
                        name: category.name,
                        slug: category.slug,
                        description: category.description ?? '',
                        sort_order: String(category.sort_order),
                        is_active: category.is_active,
                    }}
                    copy={copy}
                    submitLabel={copy.save_changes ?? 'Simpan perubahan'}
                    onSubmit={(form: DocumentCategoryFormInstance) =>
                        form.submit(updateCategory(category.id))
                    }
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
        { title: 'Kategori Dokumen', href: categoriesIndex() },
        { title: 'Ubah' },
    ],
};
