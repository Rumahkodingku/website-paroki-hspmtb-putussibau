import { useEffect, useRef, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { RotateCcw, Upload, X } from 'lucide-react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { MediaUploadError, uploadMediaFile } from '@/lib/media-client';
import { store as attachPhotos } from '@/routes/gallery-photos';
import type { SharedProps } from '@/types';

/*
 * The multi-photo uploader for one album.
 *
 * It lives in the feature, not in components/upload/, because it has exactly one
 * consumer (ARCHITECTURE Part B section 1.1) and because the shape it needs is
 * not the single-file shape the shared picker offers: every file is uploaded
 * independently, the queue keeps a per-file status, a failure is retried on its
 * own, and the settled media ids are attached to the album in one request.
 *
 * The pipeline contract is the shared one (lib/media-client.ts): a file is only
 * ever `ready` when the queued job has produced its variants, and only ready ids
 * are ever sent to /admin/galeri/{gallery}/foto. That is the same rule the
 * server enforces on media_ids, so both ends agree.
 *
 * Every uploaded file posts to /admin/media on its own, so GAL-08 holds: one
 * file's failure never discards the ones that succeeded, and retrying a failed
 * file does not touch the queue.
 */

type UploadStatus = 'uploading' | 'processing' | 'ready' | 'failed';

type UploadEntry = {
    id: string;
    file: File;
    previewUrl: string;
    status: UploadStatus;
    mediaId: number | null;
    error: string | null;
};

const STATUS_LABEL: Record<UploadStatus, string> = {
    uploading: 'Mengunggah…',
    processing: 'Memproses…',
    ready: 'Siap',
    failed: 'Gagal',
};

export function GalleryPhotoUpload({ albumId }: { albumId: number }) {
    const { media } = usePage<SharedProps>().props;
    const inputRef = useRef<HTMLInputElement>(null);
    const [entries, setEntries] = useState<UploadEntry[]>([]);
    const [submitting, setSubmitting] = useState(false);

    const entriesRef = useRef<UploadEntry[]>(entries);

    useEffect(() => {
        entriesRef.current = entries;
    });

    useEffect(() => {
        return () => {
            entriesRef.current.forEach((entry) =>
                URL.revokeObjectURL(entry.previewUrl),
            );
        };
    }, []);

    const patch = (id: string, changes: Partial<UploadEntry>) => {
        setEntries((current) =>
            current.map((entry) =>
                entry.id === id ? { ...entry, ...changes } : entry,
            ),
        );
    };

    const runUpload = async (entry: UploadEntry, file: File) => {
        patch(entry.id, { status: 'uploading', error: null });

        try {
            const result = await uploadMediaFile(file, {
                onTick: (state) => {
                    if (
                        state.status === 'processing' ||
                        state.status === 'pending'
                    ) {
                        patch(entry.id, { status: 'processing' });
                    }
                },
            });

            patch(entry.id, {
                status: 'ready',
                mediaId: result.id,
                error: null,
            });
        } catch (error) {
            const message =
                error instanceof MediaUploadError || error instanceof Error
                    ? error.message
                    : 'Gambar gagal diunggah.';

            patch(entry.id, {
                status: 'failed',
                mediaId: null,
                error: message,
            });
        }
    };

    const onSelect = (files: FileList | null) => {
        if (files === null || files.length === 0) {
            return;
        }

        const added: UploadEntry[] = Array.from(files).map((file) => ({
            id: `${file.name}-${file.size}-${file.lastModified}-${Math.random().toString(36).slice(2)}`,
            file,
            previewUrl: URL.createObjectURL(file),
            status: 'uploading',
            mediaId: null,
            error: null,
        }));

        setEntries((current) => [...current, ...added]);
        added.forEach((entry) => void runUpload(entry, entry.file));

        if (inputRef.current) {
            inputRef.current.value = '';
        }
    };

    const removeEntry = (id: string) => {
        setEntries((current) => {
            const entry = current.find((item) => item.id === id);

            if (entry) {
                URL.revokeObjectURL(entry.previewUrl);
            }

            return current.filter((item) => item.id !== id);
        });
    };

    const readyIds = entries
        .filter((entry) => entry.status === 'ready' && entry.mediaId !== null)
        .map((entry) => entry.mediaId as number);

    const attach = () => {
        if (readyIds.length === 0) {
            return;
        }

        setSubmitting(true);

        router.post(
            attachPhotos({ gallery: albumId }),
            { media_ids: readyIds },
            {
                preserveScroll: true,
                onSuccess: () => {
                    entries.forEach((entry) =>
                        URL.revokeObjectURL(entry.previewUrl),
                    );
                    setEntries([]);
                },
                onFinish: () => setSubmitting(false),
            },
        );
    };

    const limits = `${media.mimeTypes
        .map((type) => type.replace('image/', '').toUpperCase())
        .join(', ')}, maks ${media.maxUploadKilobytes / 1024} MB per foto.`;

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={() => inputRef.current?.click()}
                >
                    <Upload className="size-4" aria-hidden="true" />
                    Pilih Foto
                </Button>

                {readyIds.length > 0 ? (
                    <Button
                        type="button"
                        disabled={submitting}
                        onClick={attach}
                    >
                        Tambah ke album ({readyIds.length})
                    </Button>
                ) : null}
            </div>

            <Text as="p" variant="body-sm" color="muted">
                {limits} Format lain ditolak secara otomatis.
            </Text>

            <input
                ref={inputRef}
                type="file"
                accept={media.mimeTypes.join(',')}
                multiple
                className="sr-only"
                onChange={(event: any) => onSelect(event.target.files)}
            />

            {entries.length > 0 ? (
                <ul className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    {entries.map((entry) => (
                        <li
                            key={entry.id}
                            className="flex gap-3 rounded-lg border border-border p-3"
                        >
                            <img
                                src={entry.previewUrl}
                                alt=""
                                className="size-16 shrink-0 rounded-md object-cover"
                            />

                            <div className="min-w-0 flex-1 space-y-1">
                                <Text
                                    as="p"
                                    variant="label"
                                    className="truncate"
                                >
                                    {entry.file.name}
                                </Text>
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color={
                                        entry.status === 'failed'
                                            ? 'danger'
                                            : 'muted'
                                    }
                                >
                                    {STATUS_LABEL[entry.status]}
                                </Text>
                                {entry.error ? (
                                    <Text
                                        as="p"
                                        variant="fine-print"
                                        color="danger"
                                    >
                                        {entry.error}
                                    </Text>
                                ) : null}

                                <div className="flex items-center gap-2 pt-1">
                                    {entry.status === 'failed' ? (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() =>
                                                void runUpload(
                                                    entry,
                                                    entry.file,
                                                )
                                            }
                                        >
                                            <RotateCcw
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                            Coba lagi
                                        </Button>
                                    ) : null}

                                    {entry.status !== 'ready' ? (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            aria-label={`Batalkan ${entry.file.name}`}
                                            onClick={() =>
                                                removeEntry(entry.id)
                                            }
                                        >
                                            <X
                                                className="size-3.5"
                                                aria-hidden="true"
                                            />
                                            Batalkan
                                        </Button>
                                    ) : null}
                                </div>
                            </div>
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
    );
}
