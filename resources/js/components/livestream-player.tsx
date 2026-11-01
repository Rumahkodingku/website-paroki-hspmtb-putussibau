import { useState } from 'react';
import { Play, VideoOff } from 'lucide-react';
import { cn } from '@/lib/utils';

/**
 * Public shape of a livestream shared by the homepage and archive.
 */
export type PublicLivestream = {
    id: number;
    title: string;
    description: string | null;
    video_id: string;
    embed_url: string | null;
    watch_url: string | null;
    thumbnail_url: string | null;
    category: string | null;
    category_label: string | null;
    location: string | null;
    scheduled_at: string | null;
    started_at: string | null;
    recorded_at: string | null;
};

type Props = {
    stream: PublicLivestream;
    autoLoad?: boolean;
    className?: string;
};

/**
 * Privacy-enhanced YouTube player.
 * The iframe is mounted only when playback is requested.
 */
export function LivestreamPlayer({
    stream,
    autoLoad = false,
    className,
}: Props) {
    const [playing, setPlaying] = useState(autoLoad);

    return (
        <div
            data-test="livestream-player"
            className={cn(
                'relative isolate aspect-video w-full overflow-hidden rounded-2xl border border-border bg-black',
                className,
            )}
        >
            {playing ? (
                stream.embed_url ? (
                    <iframe
                        src={stream.embed_url}
                        title={`Video: ${stream.title}`}
                        loading="lazy"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                        allowFullScreen
                        referrerPolicy="strict-origin-when-cross-origin"
                        className="absolute inset-0 block h-full w-full border-0"
                    />
                ) : (
                    <div className="absolute inset-0 flex flex-col items-center justify-center gap-3 p-6 text-center text-white/80">
                        <VideoOff className="size-8" aria-hidden="true" />
                        <p className="text-sm">
                            Video tidak dapat diputar. Buka tautan YouTube di
                            bawah.
                        </p>
                    </div>
                )
            ) : (
                <>
                    {/* Thumbnail fills the entire video container. */}
                    {stream.thumbnail_url ? (
                        <img
                            src={stream.thumbnail_url}
                            alt=""
                            aria-hidden="true"
                            loading="lazy"
                            draggable={false}
                            className="absolute inset-0 block h-full w-full object-cover"
                        />
                    ) : (
                        <div
                            aria-hidden="true"
                            className="absolute inset-0 bg-canvas-subtle"
                        />
                    )}

                    {/* Full-size overlay: play button stays at the true center. */}
                    <button
                        type="button"
                        data-test="livestream-play"
                        onClick={() => setPlaying(true)}
                        aria-label={`Putar ${stream.title}`}
                        className="absolute inset-0 z-10 flex h-full w-full items-center justify-center overflow-hidden focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset"
                    >
                        <span className="flex size-16 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-lg transition-transform duration-200 group-hover:scale-105 hover:scale-105 motion-reduce:transform-none motion-reduce:transition-none">
                            <Play
                                className="size-7 translate-x-0.5"
                                fill="currentColor"
                                aria-hidden="true"
                            />
                        </span>
                    </button>

                    {/* Bottom label is independent of the centered play button. */}
                    {stream.thumbnail_url && (
                        <div
                            aria-hidden="true"
                            className="pointer-events-none absolute inset-x-0 bottom-0 z-20 bg-linear-to-t from-black/75 to-transparent px-4 pt-10 pb-4 text-sm font-medium text-white"
                        >
                            Tonton siaran
                        </div>
                    )}
                </>
            )}
        </div>
    );
}
