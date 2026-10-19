import type { PublicGalleryPhoto } from '../types';
import {
    MasonryGrid,
    MasonryItem,
    aspectRatioFrom,
} from '@/components/masonry-grid';
import { ParishImage } from '@/components/parish-image';

/*
 * The album's photos as a masonry grid of buttons.
 *
 * Each tile is a button rather than an anchor: it opens the lightbox in place,
 * and a link would promise a navigation that does not happen. The thumbnail
 * variant is what loads here (roadmap 8A section 8.1), so the grid never pulls
 * a full-resolution original for a picture nobody has enlarged.
 *
 * Tiles keep their own aspect ratio rather than the 1:1 square this grid used
 * before. A masonry column that crops every photo to a square is not a masonry
 * grid - it is the old grid with the seams hidden - and a fixed square is also
 * the crop DESIGN-v2 §7 warns against, since it decides which part of a
 * portrait or a wide group shot survives. `contain` letterboxes the remainder
 * against the surface instead of cutting through the subject.
 */
export function PhotoGrid({
    photos,
    onOpen,
}: {
    photos: PublicGalleryPhoto[];
    onOpen: (index: number) => void;
}) {
    return (
        <MasonryGrid className="sm:columns-3 lg:columns-4">
            {photos.map((photo, index) => (
                <MasonryItem key={photo.id}>
                    <button
                        type="button"
                        onClick={() => onOpen(index)}
                        aria-label={
                            photo.caption
                                ? `Perbesar foto: ${photo.caption}`
                                : `Perbesar foto ${index + 1}`
                        }
                        className="group block w-full overflow-hidden rounded-lg border border-border focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        {/*
                            ParishImage carries the fallback tile this grid
                            used to render by hand: a media row with no usable
                            URL still gets a sized, labelled placeholder rather
                            than an empty box.
                        */}
                        <ParishImage
                            src={photo.thumb_url ?? photo.url ?? ''}
                            alt={photo.alt_text ?? photo.caption ?? ''}
                            width={photo.width ?? 1200}
                            height={photo.height ?? 900}
                            aspectRatio={aspectRatioFrom(
                                photo.width,
                                photo.height,
                            )}
                            fit="contain"
                            className="transition-transform duration-300 group-hover:scale-[1.02] motion-reduce:transform-none motion-reduce:transition-none"
                        />
                    </button>
                </MasonryItem>
            ))}
        </MasonryGrid>
    );
}
