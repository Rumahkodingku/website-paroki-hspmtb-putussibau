'use client';

import * as React from 'react';
import {
    type UploadAdapter,
    type UploadItem as UploadItemData,
} from '@uploadcn/core';
import { compressImage } from '@uploadcn/core/image';
import { useCropQueue } from '@/hooks/use-crop-queue';
import { ImageCropper } from '@/components/ui/image-cropper';
import {
    Upload,
    UploadDropzone,
    UploadDropzoneDescription,
    UploadDropzoneHeader,
    UploadDropzoneMedia,
    UploadDropzoneTitle,
    UploadItem,
    UploadItemActions,
    UploadItemContent,
    UploadItemMedia,
    UploadItemProgress,
    UploadItemStatus,
    UploadList,
} from '@/components/ui/upload';
import { ImagePlusIcon } from 'lucide-react';

interface ImageUploadProps<TResult> {
    adapter?: UploadAdapter<TResult>;
    /**
     * Open a crop dialog for every image. Pass `{ aspect }` to fix the ratio.
     *
     * `true` is a square crop, not a free one: `ImageCropper` defaults `aspect`
     * to 1 and react-easy-crop requires a number. Passing `{ aspect: undefined }`
     * changes nothing.
     */
    crop?: boolean | { aspect?: number };
    /** Resize and re-encode images in the browser before uploading. */
    compress?: boolean;
    /** Longest edge after compression or cropping. Default 2048. */
    maxWidth?: number;
    maxHeight?: number;
    /** Encoder quality, 0–1. Default 0.82. */
    quality?: number;
    /** Output format. Defaults to WebP when compressing. */
    type?: 'image/webp' | 'image/jpeg' | 'image/avif' | 'image/png';
    maxFiles?: number;
    /** Maximum size of the original file. Default 20 MB. */
    maxSize?: number;
    /** Minimum dimensions of the original image. */
    minWidth?: number;
    minHeight?: number;
    /** What the file input accepts. Default `image/*`. */
    accept?: string;
    /** Dropzone heading. Defaults to `Add images`. */
    title?: React.ReactNode;
    /** Dropzone subheading. Defaults to the accepted formats. */
    description?: React.ReactNode;
    /** Crop dialog heading. Defaults to `Crop image`. */
    cropTitle?: React.ReactNode;
    /** Crop dialog subheading. Defaults to the library's own wording. */
    cropDescription?: React.ReactNode;
    /**
     * Replaces the per-item buttons. Defaults to the library's pause, resume,
     * retry and remove set.
     *
     * A caller that has attached the finished upload to something should say so
     * with these: the default remove button empties the queue, and on a single
     * image field an empty queue still means the image is attached.
     */
    itemActions?: React.ReactNode;
    /**
     * Rendered inside the Upload context, after the queue.
     *
     * This is how a caller reaches `UploadTrigger` — the button that opens the
     * picker — from its own markup: the context lives here, not there.
     */
    children?: React.ReactNode;
    onSuccess?: (item: UploadItemData<TResult>) => void;
    className?: string;
}

/** An image grid with optional cropping and in-browser compression. */
function ImageUpload<TResult = unknown>({
    adapter,
    crop = false,
    compress = false,
    maxWidth = 2048,
    maxHeight = 2048,
    quality = 0.82,
    type,
    maxFiles = 12,
    maxSize = 20 * 1000 * 1000,
    minWidth,
    minHeight,
    accept = 'image/*',
    title = 'Add images',
    description,
    cropTitle,
    cropDescription,
    itemActions,
    children,
    onSuccess,
    className,
}: ImageUploadProps<TResult>) {
    const crops = useCropQueue();
    const { request } = crops;
    const output = React.useMemo(
        () => ({
            maxWidth,
            maxHeight,
            quality,
            type: type ?? (compress ? ('image/webp' as const) : undefined),
        }),
        [maxWidth, maxHeight, quality, type, compress],
    );

    const transform = React.useCallback(
        async (file: File) => {
            let result = crop ? await request(file) : file;
            if (compress) result = await compressImage(result, output);
            return result;
        },
        [crop, compress, request, output],
    );

    /*
     * Assembled rather than concatenated onto the JSX: a caller that passes its
     * own description writes it in its own language, and appending an English
     * clause to it here is how a dropzone ends up half translated.
     */
    const dropzoneDescription =
        description ??
        (crop
            ? "PNG, JPG, WebP or AVIF · you'll crop each one"
            : 'PNG, JPG, WebP or AVIF');

    /*
     * The queue note wins over the caller's crop wording, because it is the more
     * urgent of the two: it is the only thing that says another file is waiting
     * behind the dialog. With one file in the queue there is nothing to say, and
     * the dialog says whatever it was given.
     */
    const cropperDescription =
        crops.pending > 1
            ? `${crops.pending - 1} more image(s) waiting.`
            : cropDescription;

    return (
        <Upload
            adapter={adapter}
            accept={accept}
            maxFiles={maxFiles}
            maxSize={maxSize}
            image={minWidth || minHeight ? { minWidth, minHeight } : undefined}
            transform={crop || compress ? transform : undefined}
            onSuccess={onSuccess}
            className={className}
        >
            <UploadDropzone size="sm">
                <UploadDropzoneHeader>
                    <UploadDropzoneMedia variant="icon">
                        <ImagePlusIcon />
                    </UploadDropzoneMedia>
                    <UploadDropzoneTitle>{title}</UploadDropzoneTitle>
                    <UploadDropzoneDescription>
                        {dropzoneDescription}
                    </UploadDropzoneDescription>
                </UploadDropzoneHeader>
            </UploadDropzone>
            <UploadList variant="grid">
                <UploadItem variant="tile">
                    <UploadItemMedia variant="cover" />
                    <UploadItemActions>{itemActions}</UploadItemActions>
                    <UploadItemContent>
                        <UploadItemStatus />
                        <UploadItemProgress />
                    </UploadItemContent>
                </UploadItem>
            </UploadList>
            {children}
            <ImageCropper
                file={crops.current}
                aspect={typeof crop === 'object' ? crop.aspect : undefined}
                output={output}
                onCrop={crops.complete}
                onCancel={crops.skip}
                {...(cropTitle === undefined ? {} : { title: cropTitle })}
                {...(cropperDescription === undefined
                    ? {}
                    : { description: cropperDescription })}
            />
        </Upload>
    );
}

export { ImageUpload, type ImageUploadProps };
