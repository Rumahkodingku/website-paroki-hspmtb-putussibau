import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as galleriesIndex,
    store as storeAlbum,
} from '@/routes/galleries';
import { GalleryForm, emptyGalleryFormData } from '../components/gallery-form';

import type { LocationOption } from '@/types/location';

type Props = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
    locations: LocationOption[];
};

export default function Create({ labels, hints, copy, locations }: Props) {
    return (
        <>
            <Head title="Tambah Album" />

            <div className="space-y-6 p-6">
                <Heading
                    title="Tambah Album"
                    description="Buat album baru, lalu tambahkan foto-fotonya."
                />

                <GalleryForm
                    initial={emptyGalleryFormData()}
                    labels={labels}
                    hints={hints}
                    copy={copy}
                    locations={locations}
                    submitLabel="Simpan album"
                    onSubmit={(form) => form.submit(storeAlbum())}
                    onCancel={() => router.visit(galleriesIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Galeri', href: galleriesIndex() },
        { title: 'Tambah' },
    ],
};
