import { Badge } from '@/components/ui/badge';
import { Text } from '@/components/text';
import type { PostStatus } from '../types';

/**
 * An article's status, as a word rather than as a colour.
 *
 * Roadmap 4.1 is explicit that status may not be signalled by colour alone, and
 * a Badge that only changes variant would do exactly that. So the label is the
 * status and the tint is decoration on top of it — which is also why a
 * screenshot of this column is still readable in greyscale.
 */
export function PostStatusBadge({ status }: { status: PostStatus }) {
    return (
        <Badge variant={status === 'published' ? 'default' : 'secondary'}>
            {STATUS_LABEL[status]}
        </Badge>
    );
}

/**
 * Whether the article is pinned, shown beside the status.
 *
 * Separate from the status badge on purpose: pinned is an editorial decision
 * and status is a lifecycle one, and folding them into one column would make a
 * reader of the list count how many are sematan by counting badges.
 */
export function PinnedBadge({ pinned }: { pinned: boolean }) {
    if (!pinned) {
        return null;
    }

    return (
        <Badge variant="outline" title="Disematkan di halaman berita">
            Disematkan
        </Badge>
    );
}

/**
 * The publication date, or why there is not one.
 *
 * A scheduled article that has not arrived says so in words rather than showing
 * an empty cell, because an empty cell next to "Terbit" is what an admin reads
 * as "this one is live".
 */
export function PostDateHint({ status }: { status: PostStatus }) {
    if (status === 'draft') {
        return <Text variant="body-sm">Belum ada tanggal</Text>;
    }

    return null;
}

const STATUS_LABEL: Record<PostStatus, string> = {
    draft: 'Draf',
    scheduled: 'Dijadwalkan',
    published: 'Terbit',
    archived: 'Diarsipkan',
};
