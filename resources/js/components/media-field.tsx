import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/form-fields';
import { Text } from '@/components/text';
import type { MediaState } from '@/types/news-agenda';

/*
 * The image picker.
 *
 * D-24 said no media library and no picker existed; this is that picker, built
 * for the one field that needed it. It is deliberately not a library: there is
 * no grid of every image the parish has ever uploaded, because that is a
 * different feature and D-24 leaves it as one.
 *
 * The flow is upload → wait → choose, and the wait is the part that matters.
 * An upload is queued (XC-M1), so a media row exists for a moment with no
 * variants on disk. Storing that id would publish an image that 404s, so the
 * form only accepts `ready` — the same condition the server's rule on media_id
 * checks, which is why both ends agree rather than the browser being the only
 * place the rule exists.
 *
 * Polling rather than a socket: the alternative for this codebase would be a
 * websocket nobody has deployed. A setInterval at 1.5s that stops the moment
 * the status settles is a smaller thing to reason about and to stop working.
 */

type Props = {
    id: string;
    label: string;
    hint?: string;
    /** The alt-text field lives beside the image, not inside it. */
    altValue: string;
    onAltChange: (value: string) => void;
    altLabel: string;
    altError?: string;
    mediaId: number | null;
    onMediaChange: (mediaId: number | null) => void;
    /** Absolute or site-relative URL of the image currently stored. */
    currentUrl: string | null;
    /** Whatever else the form posts, so the same picker edits both. */
    fieldName: string;
    /** Shown when the article or activity has no image at all. */
    emptyHint: string;
};

/** How often to ask whether the upload has finished. */
const POLL_MS = 1500;

export function MediaField({
    id,
    label,
    hint,
    altValue,
    onAltChange,
    altLabel,
    altError,
    mediaId,
    onMediaChange,
    currentUrl,
    fieldName,
    emptyHint,
}: Props) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [uploading, setUploading] = useState(false);
    const [state, setState] = useState<MediaState | null>(null);
    const [failed, setFailed] = useState<string | null>(null);

    // The poll is a ref, not a state, so restarting it on every render does
    // not restart the timer. A useEffect that depends on `state` and sets an
    // interval depending on `state` is a loop that only shows up on a slow
    // connection.
    const pollRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    useEffect(() => {
        return () => {
            if (pollRef.current !== null) {
                clearTimeout(pollRef.current);
            }
        };
    }, []);

    const pollUntilSettled = (nextMediaId: number, attempt = 0) => {
        // A hard ceiling. The job retries three times with a backoff
        // (config/media.tries), so an upload that is going to fail has failed by
        // then; a poller that never stops is a request every 1.5s until the tab
        // closes.
        if (attempt > 80) {
            setUploading(false);
            setFailed(
                'Pemrosesan gambar terlalu lama. Muat ulang halaman untuk melihat hasilnya.',
            );

            return;
        }

        pollRef.current = setTimeout(async () => {
            try {
                const response = await fetch(`/admin/media/${nextMediaId}`, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

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

                    return;
                }

                if (payload.media.status === 'ready') {
                    setUploading(false);
                    onMediaChange(nextMediaId);

                    return;
                }

                pollUntilSettled(nextMediaId, attempt + 1);
            } catch {
                setUploading(false);
                setFailed(
                    'Status gambar tidak dapat dibaca. Coba muat ulang halaman.',
                );
            }
        }, POLL_MS);
    };

    const upload = async (file: File) => {
        setUploading(true);
        setFailed(null);

        const body = new FormData();
        body.append('file', file);
        body.append('alt_text', altValue);
        body.append(fieldName, '1');

        try {
            const response = await fetch('/admin/media', {
                method: 'POST',
                body,
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    // Exactly what Inertia's own XHR client sends: the
                    // XSRF-TOKEN cookie, echoed back in the X-XSRF-TOKEN
                    // header. A <input type="file"> is not an Inertia form, so
                    // nothing attaches it for us — and a missing header here is
                    // a 419 on every upload, which reads as "the file is broken".
                    'X-XSRF-TOKEN': xsrfToken() ?? '',
                },
                credentials: 'same-origin',
            });

            if (!response.ok) {
                const payload = (await response.json().catch(() => null)) as {
                    message?: string;
                } | null;

                throw new Error(payload?.message ?? 'Unggahan ditolak.');
            }

            const payload = (await response.json()) as { media: MediaState };

            setState(payload.media);
            pollUntilSettled(payload.media.id);
        } catch (error) {
            setUploading(false);
            setFailed(
                error instanceof Error
                    ? error.message
                    : 'Gambar gagal diunggah.',
            );
        }
    };

    const preview =
        state?.variants.find((variant) => variant.name === 'medium')?.url ??
        state?.variants[0]?.url ??
        currentUrl;

    const hasImage = preview !== null && preview !== undefined;

    return (
        <div className="space-y-4">
            <Field
                id={id}
                label={label}
                hint={hint}
                error={altError && !hasImage ? altError : undefined}
            >
                <div className="flex flex-wrap items-start gap-4">
                    <div
                        className="flex size-32 shrink-0 items-center justify-center overflow-hidden rounded-md border border-hairline bg-canvas-soft"
                        data-test="media-preview"
                    >
                        {hasImage ? (
                            <img
                                src={preview}
                                alt=""
                                className="size-full object-cover"
                            />
                        ) : (
                            <Text as="p" variant="caption" color="muted">
                                {emptyHint}
                            </Text>
                        )}
                    </div>

                    <div className="flex min-w-48 flex-1 flex-col gap-2">
                        <input
                            ref={inputRef}
                            id={`${id}-file`}
                            type="file"
                            accept="image/jpeg,image/png"
                            className="sr-only"
                            onChange={(event) => {
                                const file = event.target.files?.[0];

                                if (file) {
                                    void upload(file);
                                }

                                // Cleared so re-picking the same file fires
                                // change again — otherwise an admin who chooses
                                // the wrong picture twice gets nothing.
                                event.target.value = '';
                            }}
                        />

                        <Button
                            type="button"
                            variant="outline"
                            disabled={uploading}
                            onClick={() => inputRef.current?.click()}
                        >
                            {uploading ? 'Memproses…' : 'Pilih gambar'}
                        </Button>

                        {mediaId !== null ? (
                            <Text
                                as="p"
                                variant="caption"
                                color="muted"
                                data-test="media-selected"
                            >
                                Gambar tersimpan pada artikel ini.
                            </Text>
                        ) : null}

                        {uploading ? (
                            <Text
                                as="p"
                                variant="caption"
                                color="muted"
                                role="status"
                            >
                                Gambar sedang diproses menjadi beberapa ukuran.
                            </Text>
                        ) : null}

                        {failed ? (
                            <Text
                                as="p"
                                variant="caption"
                                color="danger"
                                role="alert"
                            >
                                {failed}
                            </Text>
                        ) : null}
                    </div>
                </div>
            </Field>

            <Field id={`${id}-alt`} label={altLabel} error={altError}>
                <input
                    id={`${id}-alt`}
                    type="text"
                    value={altValue}
                    maxLength={255}
                    onChange={(event) => onAltChange(event.target.value)}
                    placeholder="Contoh: Suasana acara di halaman depan Gereja Paroki"
                    aria-describedby={`${id}-alt-hint`}
                    className="h-11 w-full rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary focus-visible:outline-none"
                />
                <Text
                    id={`${id}-alt-hint`}
                    as="p"
                    variant="caption"
                    color="muted"
                >
                    Dibaca layar pembaca. Isi dengan singkat; gambar tanpa teks
                    alternatif akan terbaca sebagai "gambar" saja.
                </Text>
            </Field>
        </div>
    );
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
