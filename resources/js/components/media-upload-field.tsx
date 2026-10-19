import { useMemo, useRef, useState } from 'react';
import { usePage } from '@inertiajs/react';
import type { UploadItem } from '@uploadcn/core';
import { useUploadContext, useUploadSelector } from '@uploadcn/react';
import { ConstructionIcon } from 'lucide-react';
import { Field } from '@/components/form-fields';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { UploadItemRemove, UploadTrigger } from '@/components/ui/upload';
import { AvatarUpload } from '@/components/upload/avatar-upload';
import { ImageUpload } from '@/components/upload/image-upload';
import { LogoUpload } from '@/components/upload/logo-upload';
import { createMediaUploadAdapter } from '@/lib/media-upload-adapter';
import type { DocumentUploadLimits, MediaLimits } from '@/types';
import type { MediaState } from '@/types/news-agenda';

/*
 * The upload field every admin form uses, chosen by `variant`.
 *
 * Six shapes live in components/upload/, and a form should not have to know
 * which one it wants or how to wire it: the adapter, the shared limits, the
 * alt text and the "the picture is still here" rule are the same six times.
 * This component is the one place that knows the difference, and three of the
 * six it deliberately does not draw. See below.
 *
 * Four things here are load-bearing:
 *
 *   - media_id is written only from a settled row. The server's rule accepts
 *     `ready` and nothing else, because an upload is queued: a media row exists
 *     before its variants do, and saving that id publishes an image that 404s.
 *     The adapter already refuses to resolve an unsettled one, so this is a
 *     second opinion rather than the only one.
 *
 *   - The picture is never hidden while the form still carries it. The queue
 *     starts empty on every page load, so a dropzone on its own says "no image"
 *     on a record that has one, and the admin reads that as "my picture is gone"
 *     and uploads it again. Every variant has to answer that, by showing the
 *     saved picture itself or by being handed a URL to show, and the same rule
 *     holds in the other direction: removing a finished upload from the queue
 *     does not detach it from the form, so the picture has to come back rather
 *     than vanish.
 *
 *   - `removable` is opt-in and off by default, because whether a null clears a
 *     column is a fact about the model, not about this component. Posts and
 *     events read null as "unchanged" (SavePostAction); board members, pastors
 *     and communities write null through. Turning the button on where the first
 *     is true gives the admin a control that detaches nothing.
 *
 *   - The limits come from the `media` shared prop, not from constants in this
 *     file. See useMediaLimits below.
 *
 * @see docs/DECISIONS.md D-62, D-63
 */

type CommonProps = {
    id: string;
    /** The picture's own name, e.g. "Gambar utama". */
    label: string;
    /** Hint for the alt text field, which is what the hint has always been for. */
    hint?: string;
    altLabel?: string;
    altValue?: string;
    onAltChange?: (value: string) => void;
    altError?: string;
    /** Browser URL of the image the record already has. */
    currentUrl: string | null;
    /** Named after the form field, sent as `1` so the upload body reads as part of the form. */
    fieldName: string;
    /**
     * Offer a control that reports the picture as removed.
     *
     * Off by default. Turn it on only where a null empties the column, because
     * where null means "unchanged" the button would detach nothing.
     */
    removable?: boolean;
    /** Told what to hold once an upload settles, or null when it is removed. */
    onMediaChange: (mediaId: number | null) => void;
};

/**
 * The variants whose backend does not exist yet carry no upload callbacks.
 *
 * Handing them `onMediaChange` would describe a contract nothing can keep: a
 * document has no row in `media`, and a gallery produces more than one row
 * while every form column holds one.
 */
type PlaceholderProps = {
    id: string;
    label: string;
    hint?: string;
};

type DocumentProps = {
    id: string;
    label: string;
    hint?: string;
    error?: string;
    required?: boolean;
    /** Name of the file the record already has, for edit forms. */
    currentFileName?: string | null;
    /** The selected replacement file, or null when it is cleared. */
    onFileChange: (file: File | null) => void;
};

export type MediaUploadFieldProps =
    | (CommonProps & {
          variant?: 'image';
          /**
           * Width / height the crop dialog locks to. Omit it and the picture is
           * not cropped at all.
           *
           * Some pictures are not framed content. A map cropped to 16:9 has had
           * streets cut off it, and it was the public box's `object-cover` that
           * did the cutting — locking a ratio here makes the form do it too,
           * deliberately and invisibly.
           */
          aspect?: number;
          /** Shown while the form carries no picture and the queue is empty. */
          emptyHint?: string;
      })
    | (CommonProps & { variant: 'avatar'; size?: number })
    | (CommonProps & {
          variant: 'logo';
          name?: string;
          /** Shown in the card while there is no logo. */
          emptyHint?: string;
      })
    | (PlaceholderProps & {
          variant: 'gallery';
          /**
           * Phase 8's contract: a gallery ends with more than one row, so it
           * reports a list rather than the single id the other variants give.
           */
          onMediaChangeMany?: (mediaIds: number[]) => void;
      })
    | (DocumentProps & { variant: 'document' })
    | (PlaceholderProps & { variant: 'file' });

export function MediaUploadField(props: MediaUploadFieldProps) {
    switch (props.variant) {
        case 'gallery':
        case 'file':
            return <UnavailableUpload {...props} />;
        case 'document':
            return <DocumentVariant {...props} />;
        case 'avatar':
            return <AvatarVariant {...props} />;
        case 'logo':
            return <LogoVariant {...props} />;
        default:
            return <ImageVariant {...props} />;
    }
}

const IMAGE_PREVIEW_CLASS = 'h-40 w-full object-contain';

function ImageVariant({
    id,
    label,
    hint,
    altLabel,
    altValue,
    onAltChange,
    altError,
    currentUrl,
    fieldName,
    removable = false,
    onMediaChange,
    aspect,
    emptyHint,
}: CommonProps & { aspect?: number; emptyHint?: string }) {
    const limits = useMediaLimits();
    const attached = useAttachedMedia(currentUrl, onMediaChange);
    const adapter = useFieldAdapter(altValue, fieldName);

    const formats = formatNames(limits.mimeTypes);
    const size = limits.maxUploadKilobytes / 1024;

    return (
        <div className="space-y-4">
            <Field id={id} label={label}>
                <ImageUpload<MediaState>
                    adapter={adapter}
                    accept={limits.mimeTypes.join(',')}
                    maxSize={limits.maxUploadKilobytes * 1024}
                    // One image is the contract; the queue reports the progress
                    // of that one, it is not a gallery.
                    maxFiles={1}
                    crop={aspect === undefined ? false : { aspect }}
                    compress
                    /*
                     * WebP is what the block compresses to by default, and this
                     * server's upload rule accepts JPEG and PNG only — so the
                     * default produces a file the pipeline rejects after the
                     * admin has already cropped it.
                     */
                    type="image/jpeg"
                    title="Unggah gambar"
                    description={
                        aspect === undefined
                            ? `${formats}, maks ${size} MB.`
                            : `${formats}, maks ${size} MB. Gambar dapat dipotong setelah dipilih.`
                    }
                    cropTitle="Potong gambar"
                    cropDescription="Geser untuk menentukan bagian gambar yang dipakai."
                    itemActions={
                        attached.applied === null ? undefined : (
                            <UploadItemRemove
                                variant="ghost"
                                size="sm"
                                className="bg-background/85 backdrop-blur-sm"
                            >
                                Ganti
                            </UploadItemRemove>
                        )
                    }
                    onSuccess={attached.apply}
                >
                    <ImageCarried
                        url={attached.url}
                        emptyHint={emptyHint}
                        gantiLabel="Ganti gambar"
                        removable={removable}
                        onRemove={attached.clear}
                    />
                </ImageUpload>
            </Field>

            <AltField
                id={`${id}-alt`}
                label={altLabel}
                value={altValue}
                hint={hint}
                error={altError}
                onChange={onAltChange}
            />
        </div>
    );
}

function AvatarVariant({
    id,
    label,
    hint,
    altLabel,
    altValue,
    onAltChange,
    altError,
    currentUrl,
    fieldName,
    removable = false,
    onMediaChange,
    size,
}: CommonProps & { size?: number }) {
    const limits = useMediaLimits();
    const attached = useAttachedMedia(currentUrl, onMediaChange);
    const adapter = useFieldAdapter(altValue, fieldName);

    return (
        <div className="space-y-4">
            <Field id={id} label={label}>
                <AvatarUpload<MediaState>
                    adapter={adapter}
                    src={attached.url}
                    size={size}
                    accept={limits.mimeTypes.join(',')}
                    maxSize={limits.maxUploadKilobytes * 1024}
                    // The server refuses WebP, and this block crops every photo
                    // before it is sent.
                    type="image/jpeg"
                    removable={removable}
                    uploadLabel="Unggah foto"
                    changeLabel="Ganti foto"
                    removeLabel="Hapus foto"
                    cropTitle="Potong foto"
                    hint={`${formatNames(limits.mimeTypes)}, maks ${limits.maxUploadKilobytes / 1024} MB. Foto dipotong menjadi persegi.`}
                    onChange={(item) => {
                        if (item === null) {
                            attached.clear();

                            return;
                        }

                        attached.apply(item);
                    }}
                />
            </Field>

            <AltField
                id={`${id}-alt`}
                label={altLabel}
                value={altValue}
                hint={hint}
                error={altError}
                onChange={onAltChange}
            />
        </div>
    );
}

function LogoVariant({
    id,
    label,
    hint,
    altLabel,
    altValue,
    onAltChange,
    altError,
    currentUrl,
    fieldName,
    removable = false,
    onMediaChange,
    name,
    emptyHint,
}: CommonProps & { name?: string; emptyHint?: string }) {
    const limits = useMediaLimits();
    const attached = useAttachedMedia(currentUrl, onMediaChange);
    const adapter = useFieldAdapter(altValue, fieldName);

    return (
        <div className="space-y-4">
            {/*
             * No Field label: the card has a title of its own, and wrapping it
             * would put the same word on screen twice.
             */}
            <LogoUpload<MediaState>
                adapter={adapter}
                src={attached.url}
                name={name}
                title={label}
                description={hint}
                accept={limits.mimeTypes.join(',')}
                maxSize={limits.maxUploadKilobytes * 1024}
                removable={removable}
                uploadLabel="Unggah logo"
                changeLabel="Ganti"
                removeLabel="Hapus logo"
                emptyLabel={emptyHint ?? 'Belum ada logo.'}
                footer={`${formatNames(limits.mimeTypes)}, maks ${limits.maxUploadKilobytes / 1024} MB, disarankan persegi.`}
                onSuccess={attached.apply}
                onRemove={attached.clear}
            />

            {/*
             * No hint here on purpose: `hint` describes the logo — formats and
             * size — and belongs under the card's title, which is where it now
             * is. The alt field asks a different question, so it gets the
             * accessibility sentence rather than the formats a second time.
             */}
            <AltField
                id={`${id}-alt`}
                label={altLabel}
                value={altValue}
                error={altError}
                onChange={onAltChange}
            />
        </div>
    );
}

/**
 * The document file picker.
 *
 * A document is submitted with its metadata in one multipart request, not
 * uploaded ahead of the form, so this is a controlled native file input rather
 * than uploadcn's uploader. The limits still come from config/documents.php
 * through the `documentUploads` shared prop, for the same reason the image
 * limits do.
 */
function DocumentVariant({
    id,
    label,
    hint,
    error,
    required = false,
    currentFileName,
    onFileChange,
}: DocumentProps) {
    const page = usePage<{ documentUploads: DocumentUploadLimits }>();
    const limits = page.props.documentUploads;
    const [localError, setLocalError] = useState<string | null>(null);

    if (limits === undefined) {
        throw new Error(
            'MediaUploadField document variant requires the documentUploads shared prop. See D-66.',
        );
    }

    const accept = limits.extensions
        .map((extension) => `.${extension}`)
        .join(',');
    const maxBytes = limits.maxUploadKilobytes * 1024;

    return (
        <Field
            id={id}
            label={label}
            hint={hint ?? formatDocumentLimits(limits)}
            error={localError ?? error}
            required={required}
        >
            <div className="space-y-2">
                {currentFileName ? (
                    <Text as="p" variant="body-sm" color="muted">
                        Berkas saat ini: {currentFileName}
                    </Text>
                ) : null}

                <input
                    id={id}
                    type="file"
                    accept={accept}
                    onChange={(event) => {
                        const file = event.currentTarget.files?.[0] ?? null;

                        if (file !== null && file.size > maxBytes) {
                            setLocalError(
                                `Ukuran berkas maksimal ${limits.maxUploadKilobytes / 1024} MB.`,
                            );
                            onFileChange(null);

                            return;
                        }

                        setLocalError(null);
                        onFileChange(file);
                    }}
                    className="block w-full cursor-pointer rounded-md border border-border bg-background px-3 py-2.5 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-secondary file:px-3 file:py-1.5 file:text-sm file:font-medium hover:file:bg-secondary/80 focus-visible:border-primary focus-visible:outline-none"
                />
            </div>
        </Field>
    );
}

function formatDocumentLimits(limits: DocumentUploadLimits): string {
    const formats = limits.extensions.map((extension) =>
        extension.toUpperCase(),
    );

    return `${formats.join(', ')}. Maksimal ${limits.maxUploadKilobytes / 1024} MB.`;
}

/**
 * The shapes whose backend is not this module yet, drawn as a notice rather
 * than a picker.
 *
 * `gallery` and `file` still have no endpoint: a gallery needs many rows while
 * every form column holds one, and a generic file row does not exist. They land
 * with their own module, if one is approved.
 */
function UnavailableUpload({ id, label, hint }: PlaceholderProps) {
    return (
        <Field id={id} label={label} hint={hint}>
            <div
                className="flex items-start gap-3 rounded-lg border border-dashed border-border bg-canvas-subtle p-4"
                data-test="media-unavailable"
            >
                <ConstructionIcon
                    aria-hidden
                    className="mt-0.5 size-4 shrink-0 text-muted-foreground"
                />
                <div className="space-y-1">
                    <Text as="p" variant="heading-sm">
                        Belum tersedia
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        Bidang ini belum tersedia. Tidak ada berkas yang dikirim
                        ke server sampai modulnya dibangun.
                    </Text>
                </div>
            </div>
        </Field>
    );
}

/** The alt text field, or nothing when the caller has no alt text to give. */
function AltField({
    id,
    label,
    value,
    hint,
    error,
    onChange,
}: {
    id: string;
    label?: string;
    value?: string;
    hint?: string;
    error?: string;
    onChange?: (value: string) => void;
}) {
    if (label === undefined || onChange === undefined) {
        return null;
    }

    return (
        <Field
            id={id}
            label={label}
            hint={hint ?? DEFAULT_ALT_HINT}
            error={error}
        >
            <input
                id={id}
                type="text"
                value={value ?? ''}
                maxLength={255}
                onChange={(event) => onChange(event.target.value)}
                placeholder="Contoh: Suasana acara di halaman depan Gereja Paroki"
                className="h-11 w-full rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary focus-visible:outline-none"
            />
        </Field>
    );
}

/**
 * What the alt field says when the caller's labels carry no hint for it.
 *
 * PRD XC-M5 makes alt text mandatory on key images, and the older MediaField
 * carried this sentence for every form. A caller with server copy of its own
 * overrides it; one without would otherwise get an input labelled "Teks
 * alternatif" and nothing explaining what belongs in it.
 */
const DEFAULT_ALT_HINT =
    'Dibaca layar pembaca. Isi dengan singkat; gambar tanpa teks alternatif akan terbaca sebagai "gambar" saja.';

/**
 * What the form is carrying, shown whenever the queue is not showing it.
 *
 * Inside the Upload context, because "is the queue empty" is the queue's state to
 * answer: it is the one thing that decides whether the tile below is redundant
 * with an item of its own or is the only description of what will be saved.
 *
 * With no picture it falls back to the caller's empty hint, which is the same
 * question answered from the other side — "there is nothing here" is worth
 * saying when the field is optional, and the dropzone alone does not say it.
 */
function ImageCarried({
    url,
    emptyHint,
    gantiLabel,
    removable,
    onRemove,
}: {
    url: string | null;
    emptyHint?: string;
    gantiLabel: string;
    removable: boolean;
    onRemove: () => void;
}) {
    const { uploader } = useUploadContext();
    const queued = useUploadSelector(
        uploader,
        (state) =>
            state.items.filter((item) => item.status !== 'rejected').length,
    );

    // The queue is showing its own tile, with its own progress and its own error.
    if (queued > 0) {
        return null;
    }

    if (url === null) {
        return emptyHint === undefined ? null : (
            <Text as="p" variant="body-sm" color="muted">
                {emptyHint}
            </Text>
        );
    }

    return (
        <div
            className="flex flex-col gap-3 rounded-lg border border-border bg-canvas-subtle p-3"
            data-test="media-current"
        >
            <img src={url} alt="" className={IMAGE_PREVIEW_CLASS} />
            <div className="flex items-center justify-between gap-3">
                {removable ? null : (
                    <Text as="p" variant="body-sm" color="muted">
                        Gambar yang sudah tersimpan pada data ini.
                    </Text>
                )}
                <div className="flex gap-2">
                    <UploadTrigger variant="outline" size="sm">
                        {gantiLabel}
                    </UploadTrigger>
                    {removable ? (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={onRemove}
                            data-test="media-remove"
                        >
                            Hapus gambar
                        </Button>
                    ) : null}
                </div>
            </div>
        </div>
    );
}

type Attached = {
    /** The row the server answered with, for a URL that can be rendered. */
    applied: MediaState | null;
    /** Report a settled upload to the form. */
    apply: (item: UploadItem<MediaState>) => void;
    /** Report a removal to the form. */
    clear: () => void;
    /** The URL to show, or null when there is nothing to show. */
    url: string | null;
};

/**
 * What the form is holding, as something that can be put on screen.
 *
 * Form state carries an id, and an id cannot be rendered: after an upload the
 * picture comes from the row the server answered with, and after a removal from
 * nowhere at all. Both have to be remembered here, because neither is in the
 * form and both stop the admin being shown a picture the form no longer has.
 */
function useAttachedMedia(
    currentUrl: string | null,
    onMediaChange: (mediaId: number | null) => void,
): Attached {
    const [applied, setApplied] = useState<MediaState | null>(null);
    const [cleared, setCleared] = useState(false);

    const apply = (item: UploadItem<MediaState>) => {
        /*
         * Settled by contract, checked here anyway: the rule on the server
         * rejects anything else, and the message the admin would get is about
         * the field rather than about the upload that produced it.
         */
        if (item.result?.status !== 'ready') {
            return;
        }

        setApplied(item.result);
        setCleared(false);
        onMediaChange(item.result.id);
    };

    const clear = () => {
        setApplied(null);
        setCleared(true);
        onMediaChange(null);
    };

    const variant = applied?.variants.find(
        (candidate) => candidate.name === 'medium',
    );

    const url = cleared
        ? null
        : (variant?.url ?? applied?.variants[0]?.url ?? currentUrl);

    return { applied, apply, clear, url };
}

/**
 * One adapter per field, with the alt text read when the file is sent.
 *
 * Reading it at render time instead would store an empty string on the media
 * row, which is the field the admin filled in before choosing the file, and
 * rebuilding the adapter on every keystroke would tear down the queue they are
 * looking at.
 */
function useFieldAdapter(altValue: string | undefined, fieldName: string) {
    const altRef = useRef(altValue);
    altRef.current = altValue;

    return useMemo(
        () =>
            createMediaUploadAdapter({
                getAltText: () => altRef.current ?? '',
                getFieldName: () => fieldName,
            }),
        [fieldName],
    );
}

/**
 * The upload limits, read from the shared prop rather than written here.
 *
 * Declared optional in the generic because it can be absent at runtime — a
 * component rendered outside a page, a test that forgot the prop — and the
 * failure it prevents is a limit that has quietly drifted from the config which
 * enforces it. Throwing names the cause; a fallback would hide it.
 */
function useMediaLimits(): MediaLimits {
    const page = usePage<{ media?: MediaLimits }>();
    const limits = page.props.media;

    if (limits === undefined) {
        throw new Error(
            'Shared prop `media` is missing. HandleInertiaRequests::share() ' +
                'provides it; see docs/DECISIONS.md D-62.',
        );
    }

    return limits;
}

/** MIME types as something an admin recognises. */
function formatNames(mimeTypes: string[]): string {
    return mimeTypes
        .map((mimeType) => mimeType.replace('image/', '').toUpperCase())
        .join(', ');
}
