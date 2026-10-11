import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    create as createPost,
    index as postsIndex,
    store as storePost,
} from '@/routes/posts';
import type { Option } from '@/types/news-agenda';
import type { PostFormMeta } from '../types';
import {
    emptyPostFormData,
    PostForm,
    type PostFormInstance,
} from '../components/post-form';

type Props = {
    postCategories: Option[];
} & Pick<PostFormMeta, 'labels' | 'hints' | 'statuses' | 'postStatusChoices'>;

export default function Create({
    postCategories,
    labels,
    hints,
    statuses,
    postStatusChoices,
}: Props) {
    const { displayTimezone } = usePage().props;

    return (
        <>
            <Head title="Tulis Artikel" />

            <div className="p-6">
                <Heading
                    title="Tulis Artikel"
                    description="Isi artikel, pilih kategori, lalu terbitkan atau jadwalkan."
                />

                <PostForm
                    initial={emptyPostFormData(postCategories)}
                    labels={labels}
                    hints={hints}
                    statuses={statuses}
                    statusChoices={postStatusChoices}
                    categories={postCategories}
                    currentImage={null}
                    displayTimezone={displayTimezone}
                    submitLabel="Simpan artikel"
                    onSubmit={(form: PostFormInstance) =>
                        form.submit(storePost())
                    }
                    onCancel={() => router.visit(postsIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Berita', href: createPost() },
        { title: 'Tulis' },
    ],
};
