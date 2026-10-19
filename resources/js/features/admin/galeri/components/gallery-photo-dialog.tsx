import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Field, FormInput } from '@/components/form-fields';
import { Text } from '@/components/text';
import type { GalleryPhotoItem } from '../types';

/*
 * Caption and alt text for one photo.
 *
 * The alt text is what a screen reader announces, so it gets a real field and a
 * hint rather than being folded into the caption (roadmap 8A section 8.3).
 */

type Props = {
    photo: GalleryPhotoItem | null;
    labels: Record<string, string>;
    hints: Record<string, string>;
    processing: boolean;
    errors: Partial<Record<'caption' | 'alt_text', string>>;
    onClose: () => void;
    onSubmit: (caption: string, altText: string) => void;
};

export function GalleryPhotoDialog({
    photo,
    labels,
    hints,
    processing,
    errors,
    onClose,
    onSubmit,
}: Props) {
    const [caption, setCaption] = useState('');
    const [altText, setAltText] = useState('');

    useEffect(() => {
        setCaption(photo?.caption ?? '');
        setAltText(photo?.alt_text ?? '');
    }, [photo]);

    return (
        <Dialog
            open={photo !== null}
            onOpenChange={(open) => {
                if (!open) {
                    onClose();
                }
            }}
        >
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Ubah Keterangan Foto</DialogTitle>
                    <DialogDescription asChild>
                        <div className="text-sm text-muted-foreground">
                            Keterangan tampil di bawah foto; teks alternatif
                            dibacakan pembaca layar.
                        </div>
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    {photo?.url ? (
                        <img
                            src={photo.url}
                            alt=""
                            className="max-h-56 w-full rounded-md object-contain"
                        />
                    ) : null}

                    <Field
                        id="photo-caption"
                        label={labels.caption ?? 'Keterangan Foto'}
                        error={errors.caption}
                    >
                        <FormInput
                            id="photo-caption"
                            value={caption}
                            onChange={setCaption}
                        />
                    </Field>

                    <Field
                        id="photo-alt"
                        label={labels.alt_text ?? 'Teks Alternatif'}
                        hint={hints.alt_text}
                        error={errors.alt_text}
                    >
                        <FormInput
                            id="photo-alt"
                            value={altText}
                            onChange={setAltText}
                        />
                    </Field>

                    <Text as="p" variant="fine-print" color="muted">
                        Pastikan foto anak-anak atau data pribadi sudah mendapat
                        izin sebelum album diterbitkan.
                    </Text>
                </div>

                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" variant="outline">
                            Batal
                        </Button>
                    </DialogClose>

                    <Button
                        type="button"
                        disabled={processing}
                        onClick={() => onSubmit(caption, altText)}
                    >
                        Simpan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
