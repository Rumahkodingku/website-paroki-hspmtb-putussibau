import { useState } from 'react';
import { router } from '@inertiajs/react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import {
    cover as setCoverRoute,
    destroy as destroyPhoto,
    reorder as reorderPhotos,
    update as updatePhoto,
} from '@/routes/gallery-photos';
import { GalleryPhotoDialog } from './gallery-photo-dialog';
import { GalleryPhotoGrid } from './gallery-photo-grid';
import { GalleryPhotoUpload } from './gallery-photo-upload';
import type { GalleryAlbumDetail, GalleryPhotoItem } from '../types';

/*
 * The whole photo manager for one album: the uploader, the ordered grid, the
 * body-sm dialog and the two confirmations.
 *
 * The order and the cover are held locally so a move or a cover pick is instant;
 * each is persisted by its own request when the admin asks. After a mutation the
 * server redirects back (Inertia), so the album prop carries the truth again and
 * the local copies are re-derived on the next render.
 *
 * Detaching never deletes the Media: the server removes the album_photo row and
 * the file stays in the library (D-65 point 4), which the confirmation says.
 */

type Props = {
    album: GalleryAlbumDetail;
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
    empty: Record<string, string>;
};

export function GalleryPhotosManager({
    album,
    labels,
    hints,
    copy,
    empty,
}: Props) {
    const [photos, setPhotos] = useState<GalleryPhotoItem[]>(album.photos);
    const [coverId, setCoverId] = useState<number | null>(
        album.cover_photo_id ?? album.photos[0]?.id ?? null,
    );
    const [dirty, setDirty] = useState(false);
    const [saving, setSaving] = useState(false);
    const [editing, setEditing] = useState<GalleryPhotoItem | null>(null);
    const [editErrors, setEditErrors] = useState<
        Partial<Record<'caption' | 'alt_text', string>>
    >({});
    const [editProcessing, setEditProcessing] = useState(false);
    const [removing, setRemoving] = useState<GalleryPhotoItem | null>(null);
    const [removeProcessing, setRemoveProcessing] = useState(false);

    const move = (index: number, by: -1 | 1) => {
        const target = index + by;

        if (target < 0 || target >= photos.length) {
            return;
        }

        const next = [...photos];
        const moved = next[index];

        if (moved === undefined) {
            return;
        }

        next[index] = next[target];
        next[target] = moved;

        setPhotos(next);
        setDirty(true);
    };

    const saveOrder = () => {
        setSaving(true);

        router.post(
            reorderPhotos({ gallery: album.id }),
            { ids: photos.map((photo) => photo.id) },
            {
                preserveScroll: true,
                onSuccess: () => setDirty(false),
                onFinish: () => setSaving(false),
            },
        );
    };

    const setCover = (photo: GalleryPhotoItem) => {
        router.post(
            setCoverRoute({ gallery: album.id }),
            { photo_id: photo.id },
            {
                preserveScroll: true,
                onSuccess: () => setCoverId(photo.id),
            },
        );
    };

    const submitEdit = (caption: string, altText: string) => {
        if (editing === null) {
            return;
        }

        setEditProcessing(true);
        setEditErrors({});

        router.patch(
            updatePhoto({ gallery: album.id, photo: editing.id }),
            { caption, alt_text: altText },
            {
                preserveScroll: true,
                onError: (errors) =>
                    setEditErrors({
                        caption: errors.caption,
                        alt_text: errors.alt_text,
                    }),
                onSuccess: () => {
                    setPhotos((current) =>
                        current.map((photo) =>
                            photo.id === editing.id
                                ? {
                                      ...photo,
                                      caption: caption === '' ? null : caption,
                                      alt_text: altText === '' ? null : altText,
                                  }
                                : photo,
                        ),
                    );
                    setEditing(null);
                },
                onFinish: () => setEditProcessing(false),
            },
        );
    };

    const confirmRemove = () => {
        if (removing === null) {
            return;
        }

        setRemoveProcessing(true);

        router.delete(destroyPhoto({ gallery: album.id, photo: removing.id }), {
            preserveScroll: true,
            onSuccess: () => {
                setPhotos((current) =>
                    current.filter((photo) => photo.id !== removing.id),
                );

                if (coverId === removing.id) {
                    setCoverId(null);
                }
            },
            onFinish: () => {
                setRemoveProcessing(false);
                setRemoving(null);
            },
        });
    };

    return (
        <div className="space-y-8">
            <div className="space-y-3">
                <Text as="h3" variant="h3">
                    Tambah Foto
                </Text>
                <Text as="p" variant="body-sm" color="muted">
                    {hints.sensitive ??
                        'Pastikan foto anak-anak atau data pribadi sudah mendapat izin sebelum diterbitkan.'}
                </Text>
                <GalleryPhotoUpload albumId={album.id} />
            </div>

            <div className="space-y-3">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Text as="h3" variant="h3">
                        {copy.manage_photos ?? 'Kelola Foto'}
                    </Text>

                    {dirty ? (
                        <Button
                            type="button"
                            disabled={saving}
                            onClick={saveOrder}
                        >
                            Simpan urutan
                        </Button>
                    ) : null}
                </div>

                {photos.length === 0 ? (
                    <div className="rounded-lg border border-dashed border-border p-10 text-center">
                        <Text as="p" variant="heading-sm">
                            {empty.photos ?? 'Belum ada foto di album ini.'}
                        </Text>
                    </div>
                ) : (
                    <>
                        {dirty ? (
                            <Text as="p" variant="body-sm" color="danger">
                                {copy.reorder_hint ?? 'Urutan belum disimpan.'}
                            </Text>
                        ) : null}

                        <GalleryPhotoGrid
                            photos={photos}
                            coverId={coverId}
                            onMoveUp={(index) => move(index, -1)}
                            onMoveDown={(index) => move(index, 1)}
                            onSetCover={setCover}
                            onEdit={(photo) => setEditing(photo)}
                            onRemove={(photo) => setRemoving(photo)}
                        />
                    </>
                )}
            </div>

            <GalleryPhotoDialog
                photo={editing}
                labels={labels}
                hints={hints}
                processing={editProcessing}
                errors={editErrors}
                onClose={() => {
                    setEditing(null);
                    setEditErrors({});
                }}
                onSubmit={submitEdit}
            />

            <ConfirmDialog
                open={removing !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setRemoving(null);
                    }
                }}
                title={copy.confirm_detach_title ?? 'Keluarkan foto ini?'}
                destructive
                disabled={removeProcessing}
                confirmLabel="Keluarkan"
                description={
                    <Text as="p" variant="body">
                        {copy.confirm_detach_body ??
                            'Foto dikeluarkan dari album. Berkas gambar tetap ada di pustaka media dan tidak dihapus.'}
                    </Text>
                }
                onConfirm={confirmRemove}
            />
        </div>
    );
}
