import { useCallback, useEffect, useRef, useState } from 'react';
import { postMediaFile, waitForMedia } from '@/lib/media-client';
import type { MediaState } from '@/types/news-agenda';

/*
 * Upload an image and wait for it to be processed.
 *
 * The insert-image button inside the rich text editor is the only consumer left.
 * It survives the picker it grew up beside (MediaField, removed in D-64) because
 * the editor is not a form field and has no uploadcn block of its own: it needs
 * the row id back so it can write an <img> into the document, which is not what
 * any of the six upload variants produce.
 *
 * The flow is upload → wait → ready, and the wait is the point. An upload is
 * queued (XC-M1), so a media row exists for a moment with no variants on disk.
 * Storing that id would publish an image that 404s, so the caller only ever
 * hears about a row once it is `ready` — the same condition the server's rule
 * on media_id checks, which is why both ends agree rather than the browser
 * being the only place the rule exists.
 *
 * The requests live in lib/media-client.ts, which the uploadcn adapter behind
 * MediaUploadField uses as well. One implementation of the poll, two callers:
 * this one renders the state as text next to a button, the uploader renders it
 * as a queue item, and neither of them is allowed to be the only one that waits.
 */

export type UploadOptions = {
    /** Stored with the media row; the editor fills it in from its dialog. */
    altText?: string;
    /**
     * A field name the form also posts, sent as `1` so the upload body looks
     * like part of the form it was triggered from. Optional; the editor does
     * not use it.
     */
    fieldName?: string;
};

export type UseMediaUpload = {
    /** The latest row the server reported, or null before the first upload. */
    state: MediaState | null;
    uploading: boolean;
    failed: string | null;
    /** Resolves with the media row once it is `ready`, or null on failure. */
    upload: (file: File, options?: UploadOptions) => Promise<MediaState | null>;
    reset: () => void;
};

export function useMediaUpload(): UseMediaUpload {
    const [state, setState] = useState<MediaState | null>(null);
    const [uploading, setUploading] = useState(false);
    const [failed, setFailed] = useState<string | null>(null);

    /*
     * The poll is abandoned, not merely ignored, when this component goes away.
     * A timer that outlives its component keeps asking the server every 1.5s
     * for a row nobody is rendering, and the admin who navigated off pays for
     * it on their next screen.
     */
    const abortRef = useRef<AbortController | null>(null);

    useEffect(() => {
        return () => {
            abortRef.current?.abort();
            abortRef.current = null;
        };
    }, []);

    const upload = useCallback(
        async (
            file: File,
            options: UploadOptions = {},
        ): Promise<MediaState | null> => {
            abortRef.current?.abort();

            const controller = new AbortController();

            abortRef.current = controller;

            setUploading(true);
            setFailed(null);

            try {
                const accepted = await postMediaFile(file, {
                    ...options,
                    signal: controller.signal,
                });

                setState(accepted);

                const settled = await waitForMedia(accepted.id, {
                    signal: controller.signal,
                    onTick: setState,
                });

                setUploading(false);

                return settled;
            } catch (error) {
                setUploading(false);

                // Leaving the page mid-upload is not something an admin did and
                // not something they need to be told about.
                if (isAbort(error)) {
                    return null;
                }

                setFailed(
                    error instanceof Error
                        ? error.message
                        : 'Gambar gagal diunggah.',
                );

                return null;
            } finally {
                if (abortRef.current === controller) {
                    abortRef.current = null;
                }
            }
        },
        [],
    );

    const reset = useCallback(() => {
        abortRef.current?.abort();
        abortRef.current = null;

        setState(null);
        setFailed(null);
        setUploading(false);
    }, []);

    return { state, uploading, failed, upload, reset };
}

function isAbort(error: unknown): boolean {
    return error instanceof DOMException && error.name === 'AbortError';
}
