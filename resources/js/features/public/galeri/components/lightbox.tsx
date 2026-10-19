import { useEffect, useRef } from 'react';
import { ChevronLeft, ChevronRight, X } from 'lucide-react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import type { PublicGalleryPhoto } from '../types';

/*
 * The album lightbox.
 *
 * Hand-rolled rather than built on the shadcn Dialog for one concrete reason:
 * the Dialog ships its own close control whose accessible name is the English
 * "Close", and this is an Indonesian interface. Building it here keeps every
 * label ours and lets the arrow-key navigation live with the thing it navigates.
 *
 * It is still accessible on purpose: role="dialog" with aria-modal, focus moves
 * to the close button on open and returns to whatever had focus when it closes,
 * Escape closes it, and the arrow keys move between photos. The caller owns the
 * open photo's index so the lightbox holds no state of its own beyond the
 * document listener.
 */

type Props = {
    photos: PublicGalleryPhoto[];
    index: number | null;
    title: string;
    onClose: () => void;
    onNavigate: (index: number) => void;
};

export function Lightbox({ photos, index, title, onClose, onNavigate }: Props) {
    const closeRef = useRef<HTMLButtonElement>(null);
    const restoreRef = useRef<HTMLElement | null>(null);

    const open = index !== null && photos[index] !== undefined;

    // Move focus in on open and back out on close, so a keyboard user is not
    // dropped at the top of the document when the lightbox closes.
    useEffect(() => {
        if (!open) {
            return;
        }

        restoreRef.current = document.activeElement as HTMLElement | null;
        closeRef.current?.focus();

        return () => {
            restoreRef.current?.focus?.();
        };
    }, [open]);

    useEffect(() => {
        if (!open || index === null) {
            return;
        }

        const onKeyDown = (event: KeyboardEvent) => {
            if (event.key === 'Escape') {
                onClose();
            }

            if (event.key === 'ArrowRight') {
                onNavigate((index + 1) % photos.length);
            }

            if (event.key === 'ArrowLeft') {
                onNavigate((index - 1 + photos.length) % photos.length);
            }
        };

        document.addEventListener('keydown', onKeyDown);

        return () => document.removeEventListener('keydown', onKeyDown);
    }, [open, index, photos.length, onClose, onNavigate]);

    if (!open || index === null) {
        return null;
    }

    const photo = photos[index];
    const previousIndex = (index - 1 + photos.length) % photos.length;
    const nextIndex = (index + 1) % photos.length;

    return (
        <div
            role="dialog"
            aria-modal="true"
            aria-label={`Foto ${index + 1} dari ${photos.length}`}
            className="fixed inset-0 z-50 flex flex-col bg-black/90 p-4 text-white"
            onClick={onClose}
        >
            <div className="flex items-center justify-between gap-3">
                <Text as="p" variant="body-sm" className="text-white/80">
                    Foto {index + 1} dari {photos.length}
                </Text>

                <Button
                    ref={closeRef}
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Tutup"
                    className="text-white hover:bg-white/10 hover:text-white"
                    onClick={(event) => {
                        event.stopPropagation();
                        onClose();
                    }}
                >
                    <X className="size-5" aria-hidden="true" />
                </Button>
            </div>

            <div
                className="flex flex-1 items-center justify-center gap-2"
                onClick={(event) => event.stopPropagation()}
            >
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Foto sebelumnya"
                    className="shrink-0 text-white hover:bg-white/10 hover:text-white"
                    onClick={() => onNavigate(previousIndex)}
                >
                    <ChevronLeft className="size-6" aria-hidden="true" />
                </Button>

                <figure className="flex max-h-full min-w-0 flex-1 flex-col items-center gap-3">
                    {photo ? (
                        <img
                            src={photo.large_url ?? photo.url ?? ''}
                            alt={photo.alt_text ?? photo.caption ?? title}
                            className="max-h-[75vh] max-w-full object-contain"
                        />
                    ) : null}

                    {photo?.caption ? (
                        <figcaption>
                            <Text
                                as="p"
                                variant="body-sm"
                                className="text-center text-white/85"
                            >
                                {photo.caption}
                            </Text>
                        </figcaption>
                    ) : null}
                </figure>

                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    aria-label="Foto berikutnya"
                    className="shrink-0 text-white hover:bg-white/10 hover:text-white"
                    onClick={() => onNavigate(nextIndex)}
                >
                    <ChevronRight className="size-6" aria-hidden="true" />
                </Button>
            </div>
        </div>
    );
}
