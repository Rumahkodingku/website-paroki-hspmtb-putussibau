import { ImageOff } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { CSSProperties } from 'react';
import Image from '@/components/image';

export type ParishImageProps = {
    src: string;
    alt: string;
    width: number;
    height: number;
    srcSet?: string;
    sizes?: string;
    aspectRatio?: string;
    priority?: boolean;
    className?: string;
    style?: CSSProperties;
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
    return (
        <Image
            src={src}
            alt={alt}
            width={width}
            height={height}
            srcSet={srcSet}
            sizes={sizes}
            aspectRatio={aspectRatio}
            priority={priority}
            fit={fit}
            className={cn('overflow-hidden', className)}
            style={style}
            fallback={
                <div
                    className="flex size-full items-center justify-center bg-secondary"
                    aria-hidden="true"
                >
                    <ImageOff className="size-6 text-subtle" />
                </div>
            }
        />
    );
}

export default ParishImage;
