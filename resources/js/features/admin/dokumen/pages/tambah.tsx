import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as documentsIndex,
    store as storeDocument,
} from '@/routes/documents';
import {
    DocumentForm,
    type DocumentFormInstance,
} from '../components/document-form';
import type {
    DocumentCategoryOption,
    DocumentFormData,
    DocumentFormMeta,
} from '../types';

type Props = {
    categories: DocumentCategoryOption[];
} & DocumentFormMeta;

function emptyData(): DocumentFormData {
    return {
        title: '',
        document_category_id: '',
        description: '',
        version_label: '',
        document_date: '',
        expires_at: '',
        status: 'draft',
        is_featured: false,
        file: null,
    };
}

export default function Create({ categories, ...meta }: Props) {
    return (
        <>
            <Head title={meta.copy.create_title ?? 'Tambah Dokumen'} />

            <div className="space-y-6 p-6">
                <Heading
                    title={meta.copy.create_title ?? 'Tambah Dokumen'}
                    description={meta.copy.create_description}
                />

                <DocumentForm
                    mode="create"
                    initial={emptyData()}
                    categories={categories}
                    meta={meta}
                    onSubmit={(form: DocumentFormInstance) =>
                        form.submit(storeDocument(), {
                            forceFormData: true,
                        })
                    }
                    onCancel={() => router.visit(documentsIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Dokumen', href: documentsIndex() },
        { title: 'Tambah' },
    ],
};
