import type { PublicGalleryPhoto } from '../types';
import {
    MasonryGrid,
    MasonryItem,
    aspectRatioFrom,
} from '@/components/masonry-grid';
import { ParishImage } from '@/components/parish-image';

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
