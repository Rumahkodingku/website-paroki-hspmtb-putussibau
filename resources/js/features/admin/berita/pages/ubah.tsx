import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { index as postsIndex, update as updatePost } from '@/routes/posts';
import type { Option } from '@/types/news-agenda';
import type { PostFormMeta, PostRow } from '../types';
import { PostForm, type PostFormInstance } from '../components/post-form';
import { toDateTimeLocalValue } from '@/lib/datetime';

type Props = {
    post: PostRow;
    postCategories: Option[];
} & Pick<PostFormMeta, 'labels' | 'hints' | 'statuses' | 'postStatusChoices'>;

export default function Edit({
    post,
    postCategories,
    labels,
    hints,
    statuses,
    postStatusChoices,
}: Props) {
    const { displayTimezone } = usePage().props;

    /*
     * The stored date is UTC; the input wants a local wall clock.
     *
     * An empty field must stay empty rather than become the epoch: a draft has
     * no date, and filling one in silently would publish it at the wrong moment.
     */
    const publishedAt =
        post.published_at === null
            ? ''
            : toDateTimeLocalValue(post.published_at, displayTimezone);

    return (
        <>
            <Head title="Ubah Artikel" />

            <div className="p-6">
                <Heading
                    title="Ubah Artikel"
                    description={`Lengkapi form untuk artikel ${post.title}.`}
                />

                <PostForm
                    initial={{
                        title: post.title,
                        // Sent back as typed. POST-01: a slug that was set once
                        // does not change because the title was reworded, and
                        // blanking the field would ask the server to generate a
                        // new one and break a URL somebody has shared.
                        slug: post.slug,
                        post_category_id: post.post_category_id,
                        author_name: post.author_name,
                        excerpt: post.excerpt ?? '',
                        content: post.content,
                        image_alt: post.image_alt ?? '',
                        scripture_reference: post.scripture_reference ?? '',
                        status: post.status,
                        published_at: publishedAt,
                        is_pinned: post.is_pinned,
                        meta_title: post.meta_title ?? '',
                        meta_description: post.meta_description ?? '',
                        media_id: post.media_id,
                    }}
                    labels={labels}
                    hints={hints}
                    statuses={statuses}
                    statusChoices={postStatusChoices}
                    categories={postCategories}
                    currentImage={post.featured_image_url}
                    displayTimezone={displayTimezone}
                    submitLabel="Simpan perubahan"
                    onSubmit={(form: PostFormInstance) =>
                        form.submit(updatePost(post.id))
                    }
                    onCancel={() => router.visit(postsIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Berita', href: postsIndex() },
        { title: 'Ubah' },
    ],
};
