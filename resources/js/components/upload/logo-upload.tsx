'use client';

import * as React from 'react';
import {
    type UploadAdapter,
    type Uploader,
    type UploadItem as UploadItemData,
} from '@uploadcn/core';
import {
    UploadItemContext,
    useUploadContext,
    useUploadSelector,
} from '@uploadcn/react';
import { cn } from '@/lib/utils';

import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    NativeButton,
    Upload,
    UploadDropzone,
    UploadItemMedia,
    UploadItemProgress,
    UploadItemRemove,
    UploadItemStatus,
    UploadTrigger,
} from '@/components/ui/upload';

interface LogoUploadProps<TResult> {
    adapter?: UploadAdapter<TResult>;
    /** Use an uploader you created with `useUploader` instead of options. */
    uploader?: Uploader<TResult>;
    /** Current logo URL, shown until a new one is uploaded. */
    src?: string | null;
    /** Shown in the placeholder. Default `"Acme"`. */
    name?: string;
    title?: string;
    description?: string;
    /** Default 2 MB. */
    maxSize?: number;
    /** What the file input accepts. */
    accept?: string;
    /**
     * Offer a control that reports the logo as removed.
     *
     * Only honest where a null clears the column. `posts` and `events` treat
     * null as "unchanged", so there the button would detach nothing. See
     * docs/DECISIONS.md D-62, D-63.
     */
    removable?: boolean;
    onRemove?: () => void;
    onSuccess?: (item: UploadItemData<TResult>) => void;
    /** Replaces the card's footer line. */
    footer?: React.ReactNode;
    /** Copy for the button that opens the picker. */
    uploadLabel?: string;
    /** Copy for the same button once there is a logo. */
    changeLabel?: string;
    /** Copy for the remove control. */
    removeLabel?: string;
    /** Line shown while there is no logo and nothing was rejected. */
    emptyLabel?: string;
    className?: string;
}

/**
 * A settings card for a workspace or company logo, with a live preview
 * on light and dark backgrounds.
 */
function LogoUpload<TResult = unknown>({
    adapter,
    uploader,
    src = null,
    name = 'Acme',
    title = 'Logo',
    description = 'Shown in the sidebar, emails and invoices.',
    maxSize = 2 * 1000 * 1000,
    accept = 'image/png,image/svg+xml,image/webp,image/jpeg',
    removable = false,
    onRemove,
    onSuccess,
    footer,
    uploadLabel = 'Upload logo',
    changeLabel = 'Replace',
    removeLabel = 'Remove',
    emptyLabel = 'Drop a file on the preview, or choose one.',
    className,
}: LogoUploadProps<TResult>) {
    return (
        <Upload
            {...(uploader
                ? { uploader }
                : {
                      adapter,
                      accept,
                      maxFiles: 1,
                      maxSize,
                      onSuccess,
                  })}
            multiple={false}
            className={className}
        >
            <Card>
                <CardHeader>
                    <CardTitle>{title}</CardTitle>
                    <CardDescription>{description}</CardDescription>
                </CardHeader>
                <LogoBody
                    name={name}
                    src={src}
                    removable={removable}
                    onRemove={onRemove}
                    uploadLabel={uploadLabel}
                    changeLabel={changeLabel}
                    removeLabel={removeLabel}
                    emptyLabel={emptyLabel}
                />
                <CardFooter className="border-t pt-4 text-xs text-muted-foreground">
                    {footer ?? (
                        <>
                            Square SVG or PNG, at least 256 × 256 px. Max{' '}
                            {maxSize / 1000 / 1000} MB.
                        </>
                    )}
                </CardFooter>
            </Card>
        </Upload>
    );
}

function Placeholder({
    name,
    className,
}: {
    name: string;
    className?: string;
}) {
    return (
        <span
            className={cn(
                'flex size-full items-center justify-center text-lg font-semibold',
                className,
            )}
        >
            {name.slice(0, 2).toUpperCase()}
        </span>
    );
}

function LogoBody({
    name,
    src,
    removable,
    onRemove,
    uploadLabel,
    changeLabel,
    removeLabel,
    emptyLabel,
}: {
    name: string;
    src: string | null;
    removable: boolean;
    onRemove?: () => void;
    uploadLabel: string;
    changeLabel: string;
    removeLabel: string;
    emptyLabel: string;
}) {
    const { uploader } = useUploadContext();
    const item = useUploadSelector(uploader, (state) =>
        state.items.find((entry) => entry.status !== 'rejected'),
    );
    const rejected = useUploadSelector(uploader, (state) =>
        state.items.find((entry) => entry.status === 'rejected'),
    );

    /*
     * The queue starts empty on every page load, so without this the card says
     * "no logo" on a record that has one. `src` is the logo already saved, which
     * is the only thing the admin can be shown before they replace it.
     */
    const stored = src === null ? null : src;

    const preview = (tone: 'light' | 'dark') => (
        <div
            className={cn(
                'flex size-16 items-center justify-center overflow-hidden rounded-xl border p-2.5',
                tone === 'light'
                    ? 'bg-card text-card-foreground'
                    : 'border-transparent bg-foreground text-background',
            )}
        >
            {item ? (
                <UploadItemMedia className="size-full border-0 bg-transparent [&_img]:object-contain" />
            ) : stored ? (
                <img src={stored} alt="" className="size-full object-contain" />
            ) : (
                <Placeholder name={name} />
            )}
        </div>
    );

    const clear = () => {
        uploader.remove();
        onRemove?.();
    };

    return (
        <UploadItemContext.Provider value={item ?? null}>
            <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-center">
                <UploadDropzone
                    variant="muted"
                    size="sm"
                    aria-label="Logo preview. Drop an image or click to choose one"
                    className="w-auto shrink-0 flex-row gap-2 p-2"
                >
                    {preview('light')}
                    {preview('dark')}
                </UploadDropzone>
                <div className="flex min-w-0 flex-1 flex-col gap-2">
                    {item ? (
                        <>
                            <div className="flex min-w-0 items-center justify-between gap-2">
                                <span className="truncate text-sm font-medium">
                                    {item.name}
                                </span>
                                <UploadItemStatus />
                            </div>
                            <UploadItemProgress />
                        </>
                    ) : (
                        <p
                            className={cn(
                                'text-sm text-muted-foreground',
                                rejected && 'text-destructive',
                            )}
                        >
                            {rejected?.issues[0]?.message ?? emptyLabel}
                        </p>
                    )}
                    <div className="flex gap-2">
                        <UploadTrigger variant="outline" size="sm">
                            {item ? changeLabel : uploadLabel}
                        </UploadTrigger>
                        {item ? (
                            <UploadItemRemove variant="ghost" size="sm">
                                {removeLabel}
                            </UploadItemRemove>
                        ) : null}
                        {/*
                         * A stored logo is not in the queue, so the block's own
                         * remove cannot reach it. This one reports the removal
                         * to the form instead, which is where the column lives.
                         */}
                        {!item && stored && removable ? (
                            <NativeButton
                                variant="ghost"
                                size="sm"
                                onClick={clear}
                            >
                                {removeLabel}
                            </NativeButton>
                        ) : null}
                    </div>
                </div>
            </CardContent>
        </UploadItemContext.Provider>
    );
}

export { LogoUpload, type LogoUploadProps };
