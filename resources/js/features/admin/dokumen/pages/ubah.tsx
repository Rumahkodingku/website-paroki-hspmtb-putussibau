import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as documentsIndex,
    update as updateDocument,
} from '@/routes/documents';
import {
    DocumentForm,
    type DocumentFormInstance,
} from '../components/document-form';
import type {
    DocumentCategoryOption,
    DocumentDetail,
    DocumentFormData,
    DocumentFormMeta,
} from '../types';

type Props = {
    document: DocumentDetail;
    categories: DocumentCategoryOption[];
} & DocumentFormMeta;

function toFormData(document: DocumentDetail): DocumentFormData {
    return {
        title: document.title,
        document_category_id: String(document.document_category_id),
        description: document.description ?? '',
        version_label: document.version_label ?? '',
        document_date: document.document_date ?? '',
        expires_at: document.expires_at ?? '',
        status: document.status,
        is_featured: document.is_featured,
        file: null,
    };
}

export default function Edit({ document, categories, ...meta }: Props) {
    return (
        <>
            <Head
                title={`${meta.copy.edit_title ?? 'Ubah Dokumen'}: ${document.title}`}
            />

            <div className="space-y-6 p-6">
                <Heading
                    title={meta.copy.edit_title ?? 'Ubah Dokumen'}
                    description={meta.copy.edit_description}
                />

                <DocumentForm
                    mode="edit"
                    initial={toFormData(document)}
                    categories={categories}
                    meta={meta}
                    currentFileName={document.original_filename}
                    onSubmit={(form: DocumentFormInstance) => {
                        form.transform((data) => ({
                            ...data,
                            _method: 'patch',
                        }));
                        form.post(updateDocument(document.id).url, {
                            forceFormData: true,
                            preserveScroll: true,
                        });
                    }}
                    onCancel={() => router.visit(documentsIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Dokumen', href: documentsIndex() },
        { title: 'Ubah' },
    ],
};
