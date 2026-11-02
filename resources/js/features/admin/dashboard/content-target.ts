import { edit as editCommunity } from '@/routes/communities';
import { edit as editDocument } from '@/routes/documents';
import { edit as editEvent } from '@/routes/events';
import { photos as galleryPhotos } from '@/routes/galleries';
import { edit as editLivestream } from '@/routes/livestreams';
import { edit as editPastor } from '@/routes/pastors';
import { edit as editPost } from '@/routes/posts';
import { edit as editService } from '@/routes/services';
import type { DashboardContentType } from './types';

/*
 * One place that turns a dashboard row into a link.
 *
 * The dashboard shows the same four kinds of content in three panels, and an
 * admin clicking an article in "Berita Terbaru" and one in "Konten Terbaru"
 * must land in the same place. Building the href in two components is how those
 * two start to differ.
 *
 * Route functions come from Wayfinder, so a moved admin route moves these links
 * with it instead of leaving a hard-coded URL behind.
 */

/** The edit screen for a content row. */
export function contentEditHref(
    type: DashboardContentType,
    id: number,
): string {
    switch (type) {
        case 'post':
            return editPost(id).url;
        case 'event':
            return editEvent(id).url;
        case 'document':
            return editDocument(id).url;
        case 'service':
            return editService(id).url;
        case 'community':
            return editCommunity(id).url;
        case 'pastor':
            return editPastor(id).url;
        case 'gallery':
            return galleryPhotos(id).url;
        case 'livestream':
            return editLivestream(id).url;
    }
}

/** The Indonesian name of a content kind, for a small type label. */
export function contentTypeLabel(type: DashboardContentType): string {
    switch (type) {
        case 'post':
            return 'Berita';
        case 'event':
            return 'Agenda';
        case 'document':
            return 'Dokumen';
        case 'service':
            return 'Pelayanan';
        case 'community':
            return 'Komunitas';
        case 'pastor':
            return 'Pastor';
        case 'gallery':
            return 'Galeri';
        case 'livestream':
            return 'Livestreaming';
    }
}
