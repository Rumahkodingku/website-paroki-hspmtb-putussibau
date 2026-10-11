import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as postCategoriesIndex,
    store as storePostCategory,
} from '@/routes/post-categories';
import type { FormMeta } from '@/types/news-agenda';
import {
    emptyPostCategoryFormData,
    PostCategoryForm,
    type PostCategoryFormInstance,
} from './components/post-category-form';

type Props = Pick<FormMeta, 'labels' | 'hints'>;

export default function Create({ labels, hints }: Props) {
    return (
        <>
            <Head title="Tambah Kategori Berita" />

            <div className="p-6">
                <Heading
                    title="Tambah Kategori Berita"
                    description="Kelompokkan artikel agar mudah dibaca dan difilter pengunjung."
                />

                <PostCategoryForm
                    initial={emptyPostCategoryFormData()}
                    labels={labels}
                    hints={hints}
                    submitLabel="Simpan kategori"
                    onSubmit={(form: PostCategoryFormInstance) =>
                        form.submit(storePostCategory())
                    }
                    onCancel={() => router.visit(postCategoriesIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Kategori Berita', href: postCategoriesIndex() },
        { title: 'Tambah' },
    ],
};
