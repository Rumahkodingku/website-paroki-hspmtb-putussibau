import { ImageOff } from 'lucide-react';
import type { PublicGalleryPhoto } from '../types';

/*
 * The album's photos as a responsive grid of buttons.
 *
 * Each tile is a button rather than an anchor: it opens the lightbox in place,
 * and a link would promise a navigation that does not happen. The thumbnail
 * variant is what loads here (roadmap 8A section 8.1), so the grid never pulls
 * a full-resolution original for a picture nobody has enlarged.
 */
export function PhotoGrid({
    photos,
    onOpen,
}: {
    photos: PublicGalleryPhoto[];
    onOpen: (index: number) => void;
}) {
    return (
        <ul className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            {photos.map((photo, index) => (
                <li key={photo.id}>
                    <button
                        type="button"
                        onClick={() => onOpen(index)}
                        aria-label={
                            photo.caption
                                ? `Perbesar foto: ${photo.caption}`
                                : `Perbesar foto ${index + 1}`
                        }
                        className="group block w-full overflow-hidden rounded-lg border border-hairline focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none"
                    >
                        {(photo.thumb_url ?? photo.url) ? (
                            <img
                                src={(photo.thumb_url ?? photo.url) as string}
                                alt={photo.alt_text ?? photo.caption ?? ''}
                                loading="lazy"
                                className="aspect-square w-full object-cover transition-transform group-hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none"
                            />
                        ) : (
                            <span className="flex aspect-square w-full items-center justify-center bg-canvas-soft text-muted-foreground">
                                <ImageOff
                                    className="size-6"
                                    aria-hidden="true"
                                />
                            </span>
                        )}
                    </button>
                </li>
            ))}
        </ul>
    );
}
