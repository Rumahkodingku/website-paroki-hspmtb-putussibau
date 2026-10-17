'use client';

import * as React from 'react';
import { type UploadAdapter, type UploadItem } from '@uploadcn/core';
import type { ImageOutputType } from '@uploadcn/core/image';
import {
    Upload as UploadPrimitive,
    useUploadContext,
    useUploadSelector,
} from '@uploadcn/react';
import { cn } from '@/lib/utils';
import { useCropQueue } from '@/hooks/use-crop-queue';
import { ImageCropper } from '@/components/ui/image-cropper';
import { NativeButton, Upload, UploadTrigger } from '@/components/ui/upload';
import { CameraIcon, UserIcon, CircleAlertIcon } from 'lucide-react';

interface AvatarUploadProps<TResult> {
    adapter?: UploadAdapter<TResult>;
    /** Current avatar URL, shown until a new one is uploaded. */
    src?: string | null;
    /** Output size in pixels (square). Default 512. */
    size?: number;
    /** Called with the upload result, or `null` when the avatar is removed. */
    onChange?: (item: UploadItem<TResult> | null) => void;
    name?: string;
    /** What the file input accepts. Default `image/*`. */
    accept?: string;
    /**
     * Encoder output for the cropped square. Default `image/webp`.
     *
     * `image/webp` is the wrong answer for a server that accepts JPEG and PNG
     * only: the admin crops the photo, and the rejected file comes back as a
     * message about a form field. See docs/DECISIONS.md D-63.
     */
    type?: ImageOutputType;
    /** Maximum size of the original file. Default 10 MB. */
    maxSize?: number;
    /** Crop dialog heading. Default `Crop your photo`. */
    cropTitle?: React.ReactNode;
    /** Copy for the button that opens the picker when there is no avatar. */
    uploadLabel?: string;
    /** Copy for the same button once there is one. */
    changeLabel?: string;
    /** Copy for the block's own remove button. */
    removeLabel?: string;
    /**
     * Whether the photo already saved can be removed through this block.
     *
     * The queue holds newly picked files, not the saved one, so without this the
     * block has nothing to remove on a record that has a photo and no new pick.
     * Only turn it on where a null clears the column: `posts` and `events` read
     * null as "unchanged". See docs/DECISIONS.md D-62, D-63.
     */
    removable?: boolean;
    /** Line under the buttons. Defaults to the accepted formats. */
    hint?: React.ReactNode;
    className?: string;
}

function AvatarUpload<TResult = unknown>({
    adapter,
    src,
    size = 512,
    onChange,
    name,
    accept = 'image/*',
    type = 'image/webp',
    maxSize = 10 * 1000 * 1000,
    cropTitle = 'Crop your photo',
    uploadLabel = 'Upload photo',
    changeLabel = 'Change photo',
    removeLabel = 'Remove',
    removable = true,
    hint,
    className,
}: AvatarUploadProps<TResult>) {
    const crops = useCropQueue();
    const { request } = crops;
    const transform = React.useCallback(
        (file: File) => request(file),
        [request],
    );

    return (
        <Upload
            adapter={adapter}
            accept={accept}
            maxSize={maxSize}
            multiple={false}
            transform={transform}
            onSuccess={onChange}
            name={name}
            className={cn('w-auto flex-row items-center gap-4', className)}
        >
            <AvatarPreview fallbackSrc={src ?? null} />
            <div className="flex flex-col gap-2">
                <div className="flex gap-2">
                    <UploadTrigger>
                        <CameraIcon data-icon="inline-start" />
                        {src ? changeLabel : uploadLabel}
                    </UploadTrigger>
                    <AvatarRemove
                        label={removeLabel}
                        stored={src !== null && src !== undefined}
                        removable={removable}
                        onRemove={() => onChange?.(null)}
                    />
                </div>
                <AvatarHint hint={hint} />
            </div>
            <ImageCropper
                file={crops.current}
                cropShape="round"
                aspect={1}
                output={{
                    maxWidth: size,
                    maxHeight: size,
                    type,
                    quality: 0.9,
                }}
                onCrop={crops.complete}
                onCancel={crops.skip}
                title={cropTitle}
            />
        </Upload>
    );
}

function useAvatarItem() {
    const { uploader } = useUploadContext();
    return useUploadSelector(uploader, (state) => state.items.at(-1) ?? null);
}

function AvatarPreview({ fallbackSrc }: { fallbackSrc: string | null }) {
    const item = useAvatarItem();
    const { openFileDialog } = useUploadContext();
    const radius = 46;
    const circumference = 2 * Math.PI * radius;
    const uploading =
        item &&
        (item.status === 'uploading' ||
            item.status === 'queued' ||
            item.status === 'validating');

    return (
        <button
            type="button"
            onClick={openFileDialog}
            aria-label="Change profile photo"
            className="group/avatar relative size-20 shrink-0 rounded-full outline-none focus-visible:ring-3 focus-visible:ring-ring/50"
        >
            <span className="absolute inset-1 flex items-center justify-center overflow-hidden rounded-full bg-muted text-muted-foreground">
                {item &&
                item.status !== 'rejected' &&
                item.status !== 'cancelled' ? (
                    <UploadPrimitive.Item
                        item={item}
                        render={<span className="contents" />}
                    >
                        <UploadPrimitive.Preview
                            thumbnailSize={256}
                            className="size-full animate-in object-cover duration-300 fade-in-0 motion-reduce:animate-none"
                        />
                    </UploadPrimitive.Item>
                ) : fallbackSrc ? (
                    // eslint-disable-next-line @next/next/no-img-element -- remote avatar URL of unknown origin
                    <img
                        src={fallbackSrc}
                        alt=""
                        className="size-full object-cover"
                    />
                ) : (
                    <UserIcon className="size-8" aria-hidden />
                )}
                <span className="absolute inset-0 flex items-center justify-center bg-background/60 opacity-0 transition-opacity group-hover/avatar:opacity-100 group-focus-visible/avatar:opacity-100">
                    <CameraIcon className="size-5" aria-hidden />
                </span>
            </span>
            {item ? (
                <UploadPrimitive.Item
                    item={item}
                    render={<span className="contents" />}
                >
                    <UploadPrimitive.Progress
                        className={cn(
                            'pointer-events-none absolute inset-0 transition-opacity',
                            !uploading && 'opacity-0',
                        )}
                        render={(props, state) => (
                            <svg
                                {...(props as React.ComponentProps<'svg'>)}
                                viewBox="0 0 100 100"
                                aria-hidden={!uploading}
                            >
                                <circle
                                    cx="50"
                                    cy="50"
                                    r={radius}
                                    fill="none"
                                    strokeWidth="4"
                                    className="stroke-muted"
                                />
                                <circle
                                    cx="50"
                                    cy="50"
                                    r={radius}
                                    fill="none"
                                    strokeWidth="4"
                                    strokeLinecap="round"
                                    strokeDasharray={circumference}
                                    strokeDashoffset={
                                        circumference *
                                        (1 - state.percent / 100)
                                    }
                                    className="origin-center -rotate-90 stroke-primary transition-[stroke-dashoffset] duration-300 motion-reduce:transition-none"
                                />
                            </svg>
                        )}
                    />
                </UploadPrimitive.Item>
            ) : null}
        </button>
    );
}

function AvatarRemove({
    label,
    stored,
    removable,
    onRemove,
}: {
    label: string;
    stored: boolean;
    removable: boolean;
    onRemove: () => void;
}) {
    const item = useAvatarItem();
    const { uploader } = useUploadContext();

    // A queued file can always be cancelled. The photo already saved is only
    // reachable when the form's column can actually be emptied.
    if (!item && !(stored && removable)) return null;

    return (
        <NativeButton
            variant="ghost"
            size="sm"
            onClick={() => {
                uploader.remove();
                onRemove();
            }}
        >
            {label}
        </NativeButton>
    );
}

function AvatarHint({ hint }: { hint?: React.ReactNode }) {
    const item = useAvatarItem();
    if (item?.status === 'error' || item?.status === 'rejected') {
        return (
            <p className="inline-flex items-center gap-1 text-xs text-destructive">
                <CircleAlertIcon className="size-3" aria-hidden />
                {item.issues[0]?.message ?? item.error?.message}
            </p>
        );
    }
    return (
        <p className="text-xs text-muted-foreground">
            {hint ?? 'JPG, PNG or WebP. Cropped to a square.'}
        </p>
    );
}

export { AvatarUpload, type AvatarUploadProps };
