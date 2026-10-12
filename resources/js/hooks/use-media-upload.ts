import { useCallback, useEffect, useRef, useState } from 'react';
import type { MediaState } from '@/types/news-agenda';

/*
 * Upload an image and wait for it to be processed.
 *
 * Two consumers need this: the article/activity image picker (MediaField) and
 * the insert-image button inside the rich text editor. It lives in hooks/
 * rather than in one of them because a copy in each is a copy that drifts, and
 * the part that matters — the poll — is exactly the part that is easy to get
 * subtly wrong.
 *
 * The flow is upload → wait → ready, and the wait is the point. An upload is
 * queued (XC-M1), so a media row exists for a moment with no variants on disk.
 * Storing that id would publish an image that 404s, so the caller only ever
 * hears about a row once it is `ready` — the same condition the server's rule
 * on media_id checks, which is why both ends agree rather than the browser
 * being the only place the rule exists.
 *
 * Polling rather than a socket: the alternative for this codebase would be a
 * websocket nobody has deployed. A setTimeout at 1.5s that stops the moment the
 * status settles is a smaller thing to reason about and to stop working.
 */

/** How often to ask whether the upload has finished. */
const POLL_MS = 1500;

/**
 * A hard ceiling. The job retries three times with a backoff
 * (config/media.tries), so an upload that is going to fail has failed by then;
 * a poller that never stops is a request every 1.5s until the tab closes.
 */
const MAX_ATTEMPTS = 80;

export type UploadOptions = {
    /** Stored with the media row; the article form fills it in. */
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

    // The poll is a ref, not state, so restarting it does not restart the
    // timer. A useEffect that depends on `state` and sets an interval depending
    // on `state` is a loop that only shows up on a slow connection.
    const pollRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (pollRef.current !== null) {
                clearTimeout(pollRef.current);
            }
        };
    }, []);

    const pollUntilSettled = useCallback(
        (mediaId: number, attempt = 0): Promise<MediaState | null> =>
            new Promise((resolve) => {
                if (attempt > MAX_ATTEMPTS) {
                    setUploading(false);
                    setFailed(
                        'Pemrosesan gambar terlalu lama. Muat ulang halaman untuk melihat hasilnya.',
                    );
                    resolve(null);

                    return;
                }

                pollRef.current = setTimeout(async () => {
                    try {
                        const response = await fetch(
                            `/admin/media/${mediaId}`,
                            {
                                headers: {
                                    Accept: 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            },
                        );

                        if (!response.ok) {
                            throw new Error(`status ${response.status}`);
                        }

                        const payload = (await response.json()) as {
                            media: MediaState;
                        };

                        setState(payload.media);

                        if (payload.media.status === 'failed') {
                            setUploading(false);
                            setFailed(
                                payload.media.error_message ??
                                    'Gambar gagal diproses. Coba unggah ulang.',
                            );
                            resolve(null);

                            return;
                        }

                        if (payload.media.status === 'ready') {
                            setUploading(false);
                            resolve(payload.media);

                            return;
                        }

                        resolve(await pollUntilSettled(mediaId, attempt + 1));
                    } catch {
                        setUploading(false);
                        setFailed(
                            'Status gambar tidak dapat dibaca. Coba muat ulang halaman.',
                        );
                        resolve(null);
                    }
                }, POLL_MS);
            }),
        [],
    );

    const upload = useCallback(
        async (
            file: File,
            options: UploadOptions = {},
        ): Promise<MediaState | null> => {
            setUploading(true);
            setFailed(null);

            const body = new FormData();
            body.append('file', file);

            if (options.altText !== undefined) {
                body.append('alt_text', options.altText);
            }

            if (options.fieldName !== undefined) {
                body.append(options.fieldName, '1');
            }

            try {
                const response = await fetch('/admin/media', {
                    method: 'POST',
                    body,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        // Exactly what Inertia's own XHR client sends: the
                        // XSRF-TOKEN cookie, echoed back in the X-XSRF-TOKEN
                        // header. A <input type="file"> is not an Inertia form,
                        // so nothing attaches it for us — and a missing header
                        // here is a 419 on every upload, which reads as "the
                        // file is broken".
                        'X-XSRF-TOKEN': xsrfToken() ?? '',
                    },
                    credentials: 'same-origin',
                });

                if (!response.ok) {
                    const payload = (await response
                        .json()
                        .catch(() => null)) as { message?: string } | null;

                    throw new Error(payload?.message ?? 'Unggahan ditolak.');
                }

                const payload = (await response.json()) as {
                    media: MediaState;
                };

                setState(payload.media);

                return await pollUntilSettled(payload.media.id);
            } catch (error) {
                setUploading(false);
                setFailed(
                    error instanceof Error
                        ? error.message
                        : 'Gambar gagal diunggah.',
                );

                return null;
            }
        },
        [pollUntilSettled],
    );

    const reset = useCallback(() => {
        setState(null);
        setFailed(null);
        setUploading(false);
    }, []);

    return { state, uploading, failed, upload, reset };
}

/**
 * The CSRF token, read from the cookie Laravel sets.
 *
 * @see node_modules/@inertiajs/core — its XhrHttpClient reads the same cookie
 * and sends the same header, which is why this is not a second convention.
 */
function xsrfToken(): string | null {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : null;
}
