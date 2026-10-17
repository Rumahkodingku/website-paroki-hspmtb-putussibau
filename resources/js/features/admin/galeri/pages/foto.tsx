import { Head, Link } from '@inertiajs/react';
import { Pencil } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { edit as editAlbum, index as galleriesIndex } from '@/routes/galleries';
import { GalleryPublicationBadge } from '../components/gallery-status-badge';
import { GalleryPhotosManager } from '../components/gallery-photos-manager';
import type { GalleryAlbumDetail } from '../types';
import { formatWibDate } from '@/lib/datetime';

type Props = {
    album: GalleryAlbumDetail;
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
    empty: Record<string, string>;
};

export default function Photos({ album, labels, hints, copy, empty }: Props) {
    return (
        <>
            <Head title={`Foto — ${album.title}`} />

            <div className="space-y-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Heading
                            title={album.title}
                            description="Kelola foto album: unggah, urutkan, pilih sampul, dan ubah keterangan."
                        />
                        <div className="flex flex-wrap items-center gap-3">
                            <GalleryPublicationBadge
                                published={album.is_published}
                            />
                            <span className="text-sm text-muted-foreground">
                                {album.event_date
                                    ? formatWibDate(album.event_date)
                                    : 'Tanggal kegiatan belum diisi'}
                                {' · '}
                                {album.photo_count} foto
                            </span>
                        </div>
                    </div>

                    <Button variant="outline" asChild>
                        <Link href={editAlbum(album.id)}>
                            <Pencil className="size-4" aria-hidden="true" />
                            Ubah Album
                        </Link>
                    </Button>
                </div>

                <GalleryPhotosManager
                    album={album}
                    labels={labels}
                    hints={hints}
                    copy={copy}
                    empty={empty}
                />
            </div>
        </>
    );
}

Photos.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Galeri', href: galleriesIndex() },
        { title: 'Kelola Foto' },
    ],
};
