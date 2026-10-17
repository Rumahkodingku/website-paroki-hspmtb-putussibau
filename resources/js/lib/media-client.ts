import type { MediaState } from '@/types/news-agenda';

/*
 * The media pipeline's two HTTP calls, with nothing React-shaped in them.
 *
 * Two consumers need them and they are not the same component: the uploadcn
 * adapter behind MediaUploadField, and the insert-image button inside the rich
 * text editor. Both post to /admin/media and both then wait for the same thing,
 * which is a status the pipeline only reaches from a queue.
 *
 * The wait is the point, and it is why this is a module rather than a hook.
 * An upload is queued (XC-M1), so a media row exists for a moment with no
 * variants on disk. Storing that id would publish an image that 404s, so the
 * caller only ever hears about a row once it is `ready` — the same condition
 * the server's rule on media_id checks, which is why both ends agree rather than
 * the browser being the only place the rule exists.
 *
 * Polling rather than a socket: the alternative for this codebase would be a
 * websocket nobody has deployed. A setTimeout at 1.5s that stops the moment the
 * status settles is a smaller thing to reason about and to stop working.
 */

/** How often to ask whether the upload has finished. */
export const MEDIA_POLL_MS = 1500;

/**
 * A hard ceiling. The job retries three times with a backoff
 * (config/media.tries), so an upload that is going to fail has failed by then;
 * a poller that never stops is a request every 1.5s until the tab closes.
 */
export const MEDIA_POLL_MAX_ATTEMPTS = 80;

export type PostMediaOptions = {
    /** Stored with the media row; the article form fills it in. */
    altText?: string;
    /**
     * A field name the form also posts, sent as `1` so the upload body looks
     * like part of the form it was triggered from. Optional; the editor does
     * not use it.
     */
    fieldName?: string;
    signal?: AbortSignal;
};

/** What the caller gets when an upload cannot produce a usable media row. */
/**
 * An upload that cannot produce a usable media row.
 *
 * Carries what the uploader needs to decide whether trying again could ever
 * help, because a 413 answered three times with a widening gap is one clear
 * message stretched into three, and the admin watching it is the one who pays.
 */
export class MediaUploadError extends Error {
    /** Retrying cannot change the answer: the server said no, or waited long enough. */
    readonly retryable: boolean;

    /** The response status, when the failure came from one. */
    readonly status: number | undefined;

    constructor(
        message: string,
        {
            retryable = false,
            status,
        }: { retryable?: boolean; status?: number } = {},
    ) {
        super(message);
        this.name = 'MediaUploadError';
        this.retryable = retryable;
        this.status = status;
    }
}

/**
 * Send one file to the pipeline.
 *
 * Throws MediaUploadError rather than returning null so the uploadcn adapter can
 * turn it into an item error state, which is the difference between "try again"
 * and "nothing happened". use-media-upload still catches it and surfaces it as
 * state, because that is what its callers render.
 */
export async function postMediaFile(
    file: File,
    { altText, fieldName, signal }: PostMediaOptions = {},
): Promise<MediaState> {
    const body = new FormData();
    body.append('file', file);

    if (altText !== undefined) {
        body.append('alt_text', altText);
    }

    if (fieldName !== undefined) {
        body.append(fieldName, '1');
    }

    let response: Response;

    try {
        response = await fetch('/admin/media', {
            method: 'POST',
            body,
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                // Exactly what Inertia's own XHR client sends: the
                // XSRF-TOKEN cookie, echoed back in the X-XSRF-TOKEN header.
                // A <input type="file"> is not an Inertia form, so nothing
                // attaches it for us — and a missing header here is a 419 on
                // every upload, which reads as "the file is broken".
                'X-XSRF-TOKEN': readXsrfToken() ?? '',
            },
            credentials: 'same-origin',
            ...(signal ? { signal } : {}),
        });
    } catch (error) {
        // An aborted request is the caller cancelling, not a failure worth a
        // message. Rethrown so the uploader can tell the two apart.
        if (isAbortError(error)) {
            throw error;
        }

        throw new MediaUploadError(
            'Gambar gagal diunggah. Periksa koneksi lalu coba lagi.',
            { retryable: true },
        );
    }

    if (!response.ok) {
        const payload = (await response.json().catch(() => null)) as {
            message?: string;
        } | null;

        throw new MediaUploadError(payload?.message ?? 'Unggahan ditolak.', {
            status: response.status,
        });
    }

    const payload = (await response.json()) as { media: MediaState };

    return payload.media;
}

/**
 * Ask for one upload's state until it stops changing.
 *
 * Resolves with the settled row — `ready` or `failed` — and never with a row
 * that is still in flight, because the only caller who acts on the answer writes
 * it to a form. Throws MediaUploadError when the pipeline reported a failure or
 * when the wait ran out, so a caller cannot mistake "gave up" for "succeeded".
 */
export async function waitForMedia(
    mediaId: number,
    {
        signal,
        onTick,
    }: { signal?: AbortSignal; onTick?: (media: MediaState) => void } = {},
): Promise<MediaState> {
    for (let attempt = 0; attempt < MEDIA_POLL_MAX_ATTEMPTS; attempt += 1) {
        await sleep(MEDIA_POLL_MS, signal);

        const payload = await readMedia(mediaId, signal);

        onTick?.(payload);

        if (payload.status === 'failed') {
            throw new MediaUploadError(
                payload.error_message ??
                    'Gambar gagal diproses. Coba unggah ulang.',
            );
        }

        if (payload.status === 'ready') {
            return payload;
        }
    }

    throw new MediaUploadError(
        'Pemrosesan gambar terlalu lama. Muat ulang halaman untuk melihat hasilnya.',
    );
}

/**
 * Upload and wait, which is the only sequence either caller actually wants.
 */
export async function uploadMediaFile(
    file: File,
    options: PostMediaOptions & { onTick?: (media: MediaState) => void } = {},
): Promise<MediaState> {
    const accepted = await postMediaFile(file, options);

    return waitForMedia(accepted.id, {
        signal: options.signal,
        onTick: options.onTick,
    });
}

/** One read of the pipeline's state for a row. */
async function readMedia(
    mediaId: number,
    signal?: AbortSignal,
): Promise<MediaState> {
    let response: Response;

    try {
        response = await fetch(`/admin/media/${mediaId}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            ...(signal ? { signal } : {}),
        });
    } catch (error) {
        if (isAbortError(error)) {
            throw error;
        }

        throw new MediaUploadError(
            'Status gambar tidak dapat dibaca. Coba muat ulang halaman.',
            { retryable: true },
        );
    }

    if (!response.ok) {
        throw new MediaUploadError(
            'Status gambar tidak dapat dibaca. Coba muat ulang halaman.',
            { retryable: true, status: response.status },
        );
    }

    const payload = (await response.json()) as { media: MediaState };

    return payload.media;
}

/**
 * Wait, cancellably.
 *
 * A plain setTimeout keeps the timer alive after the admin has navigated away,
 * and the callback then reads a state nobody is showing any more.
 */
function sleep(ms: number, signal?: AbortSignal): Promise<void> {
    return new Promise((resolve, reject) => {
        if (signal?.aborted === true) {
            reject(new DOMException('Aborted', 'AbortError'));

            return;
        }

        const timer = setTimeout(() => {
            signal?.removeEventListener('abort', onAbort);
            resolve();
        }, ms);

        function onAbort() {
            clearTimeout(timer);
            reject(new DOMException('Aborted', 'AbortError'));
        }

        signal?.addEventListener('abort', onAbort, { once: true });
    });
}

function isAbortError(error: unknown): boolean {
    return error instanceof DOMException && error.name === 'AbortError';
}

/**
 * The CSRF token, read from the cookie Laravel sets.
 *
 * @see node_modules/@inertiajs/core — its XhrHttpClient reads the same cookie
 * and sends the same header, which is why this is not a second convention.
 */
export function readXsrfToken(): string | null {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]*)/);

    return match ? decodeURIComponent(match[1]) : null;
}
