import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as categoriesIndex,
    store as storeCategory,
} from '@/routes/document-categories';
import {
    DocumentCategoryForm,
    emptyDocumentCategoryFormData,
    type DocumentCategoryFormInstance,
} from './components/document-category-form';

type Props = {
    copy: Record<string, string>;
};

export default function Create({ copy }: Props) {
    return (
        <>
            <Head title={copy.create_title ?? 'Tambah Kategori Dokumen'} />

            <div className="p-6">
                <Heading
                    title={copy.create_title ?? 'Tambah Kategori Dokumen'}
                    description={copy.description}
                />

                <DocumentCategoryForm
                    initial={emptyDocumentCategoryFormData()}
                    copy={copy}
                    submitLabel={copy.save ?? 'Simpan kategori'}
                    onSubmit={(form: DocumentCategoryFormInstance) =>
                        form.submit(storeCategory())
                    }
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
        { title: 'Kategori Dokumen', href: categoriesIndex() },
        { title: 'Tambah' },
    ],
};
