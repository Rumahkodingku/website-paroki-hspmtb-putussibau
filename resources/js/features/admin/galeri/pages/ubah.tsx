import { Head, Link, router } from '@inertiajs/react';
import { Images } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import {
    index as galleriesIndex,
    photos as albumPhotos,
    update as updateAlbum,
} from '@/routes/galleries';
import { GalleryForm } from '../components/gallery-form';
import type { GalleryAlbumFormInstance } from '../components/gallery-form-instance';
import type { GalleryAlbumDetail, GalleryAlbumFormData } from '../types';

type Props = {
    album: GalleryAlbumDetail;
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
};

export default function Edit({ album, labels, hints, copy }: Props) {
    const initial: GalleryAlbumFormData = {
        title: album.title,
        slug: album.slug,
        description: album.description ?? '',
        event_date: album.event_date ?? '',
        location: album.location ?? '',
        is_published: album.is_published,
    };

    const submit = (form: GalleryAlbumFormInstance) => {
        form.submit(updateAlbum(album.id));
    };

    return (
        <>
            <Head title={`Ubah ${album.title}`} />

            <div className="space-y-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <Heading
                        title="Ubah Album"
                        description={`Perbarui album "${album.title}".`}
                    />

                    <Button variant="outline" asChild>
                        <Link href={albumPhotos(album.id)}>
                            <Images className="size-4" aria-hidden="true" />
                            Kelola Foto ({album.photo_count})
                        </Link>
                    </Button>
                </div>

                <GalleryForm
                    initial={initial}
                    labels={labels}
                    hints={hints}
                    copy={copy}
                    submitLabel="Simpan perubahan"
                    onSubmit={submit}
                    onCancel={() => router.visit(galleriesIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Galeri', href: galleriesIndex() },
        { title: 'Ubah' },
    ],
};
