import { Badge } from '@/components/ui/badge';

/**
 * Draft / Terbit as a labelled badge.
 *
 * Text, not colour alone (roadmap 8A section 9): a status the reader can only
 * tell apart by its colour is one they cannot tell apart at all.
 */
export function GalleryPublicationBadge({ published }: { published: boolean }) {
    return (
        <Badge variant={published ? 'default' : 'secondary'}>
            {published ? 'Terbit' : 'Draf'}
        </Badge>
    );
}
