import { ImageOff } from 'lucide-react';
import { useState, type CSSProperties } from 'react';
import { cn } from '@/lib/utils';

export type ParishImageProps = {
    src: string;
    alt: string;
    width: number;
    height: number;
    srcSet?: string;
    sizes?: string;
    /** 16/9 unless a portrait or a fixed panel is being shown. */
    aspectRatio?: string;
    /** True for above-the-fold imagery only. */
    priority?: boolean;
    className?: string;
    style?: CSSProperties;
    /** object-cover crops; object-contain letterboxes. */
    fit?: 'cover' | 'contain';
};

export function ParishImage({
    src,
    alt,
    width,
    height,
    srcSet,
    sizes,
    aspectRatio = '16 / 9',
    priority = false,
    className,
    style,
    fit = 'cover',
}: ParishImageProps) {
    const [failed, setFailed] = useState(false);

    return (
        <div
            className={cn('relative overflow-hidden', className)}
            style={{ aspectRatio, ...style }}
        >
            {failed ? (
                /*
                 * secondary, not a literal navy-light. navy-light is a near-white
                 * surface and the dark theme's ink is a near-white colour too, so
                 * the pair measured 1.04:1 - a placeholder nobody can see, which
                 * is the one thing a placeholder must not be.
                 */
                <div
                    className="flex size-full items-center justify-center bg-secondary"
                    aria-hidden="true"
                >
                    <ImageOff className="size-6 text-ink-muted-soft" />
                </div>
            ) : (
                <img
                    src={src}
                    alt={alt}
                    width={width}
                    height={height}
                    srcSet={srcSet}
                    sizes={sizes}
                    loading={priority ? 'eager' : 'lazy'}
                    fetchPriority={priority ? 'high' : 'auto'}
                    decoding={priority ? 'sync' : 'async'}
                    onError={() => setFailed(true)}
                    className={cn(
                        'size-full',
                        fit === 'cover' ? 'object-cover' : 'object-contain',
                    )}
                />
            )}
        </div>
    );
}

export default ParishImage;
