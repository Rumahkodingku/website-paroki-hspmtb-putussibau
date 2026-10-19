import {
    ArrowDown,
    ArrowUp,
    ImageOff,
    Pencil,
    Star,
    Trash,
} from 'lucide-react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { cn } from '@/lib/utils';
import type { GalleryPhotoItem } from '../types';

/*
 * The album's photos as an ordered grid.
 *
 * The order shown is the local order the manager keeps; moving a row only
 * reorders locally, and the manager saves the whole list in one request when
 * asked. That is deliberate: a reorder is a single fact — "this is the order" —
 * and saving after every arrow would send one request per keypress.
 *
 * No colour-only signals: the cover is marked with a badge and a label, and each
 * control has an accessible name.
 */

type Props = {
    photos: GalleryPhotoItem[];
    coverId: number | null;
    onMoveUp: (index: number) => void;
    onMoveDown: (index: number) => void;
    onSetCover: (photo: GalleryPhotoItem) => void;
    onEdit: (photo: GalleryPhotoItem) => void;
    onRemove: (photo: GalleryPhotoItem) => void;
};

export function GalleryPhotoGrid({
    photos,
    coverId,
    onMoveUp,
    onMoveDown,
    onSetCover,
    onEdit,
    onRemove,
}: Props) {
    return (
        <ul className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            {photos.map((photo, index) => {
                const effectiveCoverId = coverId ?? photos[0]?.id ?? null;
                const isCover = photo.id === effectiveCoverId;

                return (
                    <li
                        key={photo.id}
                        className={cn(
                            'overflow-hidden rounded-lg border bg-background',
                            isCover ? 'border-primary' : 'border-border',
                        )}
                    >
                        <div className="relative aspect-4/3 bg-muted">
                            {photo.url ? (
                                <img
                                    src={photo.url}
                                    alt={photo.alt_text ?? ''}
                                    loading="lazy"
                                    className="size-full object-cover"
                                />
                            ) : (
                                <span className="flex size-full items-center justify-center text-muted-foreground">
                                    <ImageOff
                                        className="size-6"
                                        aria-hidden="true"
                                    />
                                </span>
                            )}

                            {isCover ? (
                                <span className="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full bg-primary px-2 py-0.5 text-xs font-medium text-primary-foreground">
                                    <Star
                                        className="size-3"
                                        aria-hidden="true"
                                    />
                                    Sampul
                                </span>
                            ) : null}
                        </div>

                        <div className="space-y-2 p-3">
                            <Text
                                as="p"
                                variant="body-sm"
                                color="muted"
                                className="line-clamp-2 min-h-8"
                            >
                                {photo.caption ?? 'Tanpa keterangan'}
                            </Text>

                            <div className="flex flex-wrap items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Naikkan urutan"
                                    disabled={index === 0}
                                    onClick={() => onMoveUp(index)}
                                >
                                    <ArrowUp
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Button>

                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    aria-label="Turunkan urutan"
                                    disabled={index === photos.length - 1}
                                    onClick={() => onMoveDown(index)}
                                >
                                    <ArrowDown
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </Button>

                                {!isCover ? (
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        onClick={() => onSetCover(photo)}
                                    >
                                        Jadikan sampul
                                    </Button>
                                ) : null}

                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Ubah keterangan"
                                            onClick={() => onEdit(photo)}
                                        >
                                            <Pencil
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                        </Button>
                                    </TooltipTrigger>

                                    <TooltipContent>
                                        <p>Ubah keterangan</p>
                                    </TooltipContent>
                                </Tooltip>

                                <Tooltip>
                                    <TooltipTrigger asChild>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="icon"
                                            aria-label="Keluarkan foto"
                                            className="text-primary-on-surface"
                                            onClick={() => onRemove(photo)}
                                        >
                                            <Trash
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                        </Button>
                                    </TooltipTrigger>

                                    <TooltipContent>
                                        <p>Keluarkan dari album</p>
                                    </TooltipContent>
                                </Tooltip>
                            </div>
                        </div>
                    </li>
                );
            })}
        </ul>
    );
}
