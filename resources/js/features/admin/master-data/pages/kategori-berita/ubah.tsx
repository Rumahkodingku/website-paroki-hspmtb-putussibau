import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as postCategoriesIndex,
    update as updatePostCategory,
} from '@/routes/post-categories';
import type { FormMeta } from '@/types/news-agenda';
import {
    PostCategoryForm,
    type PostCategoryFormInstance,
} from './components/post-category-form';
import type { PostCategoryRow } from '../../types/post-category';

type Props = {
    postCategory: PostCategoryRow;
} & Pick<FormMeta, 'labels' | 'hints'>;

export default function Edit({ postCategory, labels, hints }: Props) {
    return (
        <>
            <Head title="Ubah Kategori Berita" />

            <div className="p-6">
                <Heading
                    title="Ubah Kategori Berita"
                    description={`Lengkapi form untuk kategori ${postCategory.name}.`}
                />

                <PostCategoryForm
                    initial={{
                        name: postCategory.name,
                        slug: postCategory.slug,
                        sort_order: postCategory.sort_order,
                    }}
                    labels={labels}
                    hints={hints}
                    submitLabel="Simpan perubahan"
                    onSubmit={(form: PostCategoryFormInstance) =>
                        form.submit(updatePostCategory(postCategory.id))
                    }
                    onCancel={() => router.visit(postCategoriesIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Kategori Berita', href: postCategoriesIndex() },
        { title: 'Ubah' },
    ],
};
