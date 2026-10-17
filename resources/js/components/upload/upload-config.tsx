'use client';

import { UploadConfigProvider } from '@uploadcn/react';
import { mediaUploadAdapter } from '@/lib/media-upload-adapter';

/**
 * Where every upload in the app goes.
 *
 * It used to be `s3Adapter({ endpoint: '/api/upload' })`, which is the shape
 * uploadcn's own docs ship with and the shape nothing here speaks: this
 * application has a media pipeline (POST /admin/media, a database row, a queued
 * job) and no `/api/upload` route at all. Every uploader wired to that endpoint
 * is not a visibly broken uploader — the dropzone, the progress bar, the crop
 * dialog and the retry button all keep working perfectly around a 404.
 *
 * So the adapter is the Laravel one, from the same factory the form fields use,
 * and it is the only place that decides where bytes go.
 *
 * No maxSize here on purpose: the accepted size and MIME types are
 * `config/media.php`'s to decide and arrive as the `media` shared prop, so a
 * limit can only be changed in one place. A generous default on the provider
 * would be a second answer to the same question.
 */
export function UploadConfig({ children }: { children: React.ReactNode }) {
    return (
        <UploadConfigProvider adapter={mediaUploadAdapter}>
            {children}
        </UploadConfigProvider>
    );
}
