import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Field } from '@/components/form-fields';
import { Text } from '@/components/text';
import { useMediaUpload } from '@/hooks/use-media-upload';

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
    /** Browser URL of the image currently stored. */
    currentUrl: string | null;
    /** Whatever else the form posts, so the same picker edits both. */
    fieldName: string;
    /** Shown when the article or activity has no image at all. */
    emptyHint: string;
};

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
    const { state, uploading, failed, upload } = useMediaUpload();

    const [localPreview, setLocalPreview] = useState<string | null>(null);
    const localPreviewRef = useRef<string | null>(null);

    const replaceLocalPreview = (url: string | null) => {
        if (
            localPreviewRef.current !== null &&
            localPreviewRef.current !== url
        ) {
            URL.revokeObjectURL(localPreviewRef.current);
        }

        localPreviewRef.current = url;
        setLocalPreview(url);
    };

    useEffect(() => {
        return () => {
            if (localPreviewRef.current !== null) {
                URL.revokeObjectURL(localPreviewRef.current);
            }
        };
    }, []);

    const handleFile = async (file: File) => {
        replaceLocalPreview(URL.createObjectURL(file));

        const media = await upload(file, { altText: altValue, fieldName });

        if (media) {
            onMediaChange(media.id);
        }

        replaceLocalPreview(null);
    };

    const serverPreview =
        state?.variants.find((variant) => variant.name === 'medium')?.url ??
        state?.variants[0]?.url ??
        currentUrl;

    const preview = localPreview ?? serverPreview;
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
                                    void handleFile(file);
                                }

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
                    className="mt-2"
                >
                    Dibaca layar pembaca. Isi dengan singkat; gambar tanpa teks
                    alternatif akan terbaca sebagai "gambar" saja.
                </Text>
            </Field>
        </div>
    );
}
