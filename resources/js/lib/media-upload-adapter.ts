import { createAdapter, UploadError, type UploadAdapter } from '@uploadcn/core';
import {
    MediaUploadError,
    uploadMediaFile,
    type PostMediaOptions,
} from '@/lib/media-client';
import type { MediaState } from '@/types/news-agenda';

/*
 * Teaches uploadcn's uploader about this application's media pipeline.
 *
 * The provider in components/upload/upload-config.tsx shipped pointing at
 * `/api/upload`, which is a route this application does not have: the pipeline
 * is POST /admin/media, a database row, and a queued job. A uploader wired to
 * the other endpoint is not a broken uploader, it is a 404 on every attempt, and
 * the cropper, the progress bar and the retry button all still work perfectly
 * around it.
 *
 * So the adapter is the only piece that knows where bytes go. Everything above
 * it — the dropzone, the queue, the crop dialog — stays the library's, and the
 * endpoint question stays in one file.
 *
 * The result is a MediaState, and it is always a settled one: postMediaFile and
 * waitForMedia between them refuse to return a row that is still in the queue.
 * That is what lets the caller write result.id straight into a form, given that
 * the server's own rule on media_id rejects anything that is not ready.
 */

export type MediaUploadAdapterOptions = {
    /**
     * Read at upload time, not at construction time.
     *
     * The alt text lives in a form field the admin fills in before choosing the
     * file, and the adapter is built once per render. A getter rather than a
     * value is what keeps the two in step without rebuilding the adapter — and
     * rebuilding it mid-queue would drop the files already in flight.
     */
    getAltText?: () => string;
    /**
     * A field name the form also posts, sent as `1` so the upload body reads as
     * part of the form it was triggered from.
     */
    getFieldName?: () => string;
};

export function createMediaUploadAdapter(
    options: MediaUploadAdapterOptions = {},
): UploadAdapter<MediaState> {
    return createAdapter<MediaState>(
        async ({ file, signal }) => {
            const request: PostMediaOptions = {};

            const altText = options.getAltText?.();

            if (altText !== undefined) {
                request.altText = altText;
            }

            const fieldName = options.getFieldName?.();

            if (fieldName !== undefined) {
                request.fieldName = fieldName;
            }

            if (signal !== undefined) {
                request.signal = signal;
            }

            try {
                return await uploadMediaFile(file, request);
            } catch (error) {
                throw asUploadError(error);
            }
        },
        {
            name: 'laravel-media',
        },
    );
}

/**
 * The adapter the whole application shares.
 *
 * No alt text and no field name: this is the one used by anything that is not
 * a form field of its own, and a form that needs either builds its own from the
 * same factory rather than the global one trying to guess which form it is in.
 */
export const mediaUploadAdapter = createMediaUploadAdapter();

/**
 * Say whether the engine may try again.
 *
 * A 413 or a 422 is this application's own answer and will be the same answer
 * next time, so retrying it three times turns one clear message into three. A
 * dropped connection is not in that group, so the library's own backoff still
 * covers it.
 */
function asUploadError(error: unknown): UploadError {
    if (error instanceof MediaUploadError) {
        return new UploadError(error.message, {
            code: error.retryable ? 'network' : 'http',
            retryable: error.retryable,
            ...(error.status === undefined ? {} : { status: error.status }),
            cause: error,
        });
    }

    return error instanceof Error
        ? new UploadError(error.message, { code: 'unknown', cause: error })
        : new UploadError('Gambar gagal diunggah.', { code: 'unknown' });
}
