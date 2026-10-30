import { RichText } from '@/components/rich-text';
import Image from '@/components/image';
import { Text } from '@/components/text';
import type { PublicHistoryItem } from '../types';

export function HistoryTimeline({ items }: { items: PublicHistoryItem[] }) {
    return (
        <ol className="relative">
            {/* Garis timeline desktop */}
            <span
                aria-hidden="true"
                className="absolute top-4 bottom-4 left-1/2 hidden w-px -translate-x-1/2 bg-border lg:block"
            />

            {/* Garis timeline mobile dan tablet */}
            <span
                aria-hidden="true"
                className="absolute top-3 bottom-3 left-1.25 w-px bg-border lg:hidden"
            />

            {items.map((item, index) => {
                const isEven = index % 2 === 0;
                const year = item.date ?? item.year ?? 'Tahun tidak diketahui';

                return (
                    <li
                        key={item.id}
                        className="relative pb-10 last:pb-0 sm:pb-12 lg:pb-16"
                    >
                        {/* Titik timeline */}
                        <span
                            aria-hidden="true"
                            className="absolute top-1.5 left-0 z-10 size-2.75 rounded-full border-2 border-primary bg-background lg:top-5 lg:left-1/2 lg:-translate-x-1/2"
                        />

                        <article className="grid grid-cols-1 gap-4 pl-7 sm:gap-5 lg:grid-cols-[minmax(0,1fr)_48px_minmax(0,1fr)] lg:items-start lg:gap-0 lg:pl-0">
                            {/* Gambar */}
                            <div
                                className={[
                                    'min-w-0',
                                    isEven
                                        ? 'lg:col-start-1 lg:row-start-1'
                                        : 'lg:col-start-3 lg:row-start-1',
                                ].join(' ')}
                            >
                                {item.image ? (
                                    <Image
                                        src={item.image}
                                        alt={item.image_alt ?? ''}
                                        loading="lazy"
                                        decoding="async"
                                        aspectRatio="16 / 9"
                                        fit="cover"
                                        imageClassName="shadow-sm ring-1 ring-black/5"
                                        className="w-full rounded-xl"
                                    />
                                ) : (
                                    <div className="flex aspect-video w-full items-center justify-center rounded-xl border border-dashed border-border bg-muted/30">
                                        <Text
                                            as="span"
                                            variant="body-sm"
                                            color="muted"
                                        >
                                            Foto belum tersedia
                                        </Text>
                                    </div>
                                )}
                            </div>

                            {/* Konten */}
                            <div
                                className={[
                                    'min-w-0',
                                    isEven
                                        ? 'lg:col-start-3 lg:row-start-1'
                                        : 'lg:col-start-1 lg:row-start-1',
                                ].join(' ')}
                            >
                                <Text
                                    as="p"
                                    variant="display-md"
                                    color="danger"
                                    className="leading-none"
                                >
                                    {year}
                                </Text>

                                <Text as="h3" variant="h4" className="mt-2">
                                    {item.title}
                                </Text>

                                {item.description ? (
                                    <RichText
                                        html={item.description}
                                        className="mt-2 max-w-xl"
                                    />
                                ) : null}
                            </div>
                        </article>
                    </li>
                );
            })}
        </ol>
    );
}

export default HistoryTimeline;
