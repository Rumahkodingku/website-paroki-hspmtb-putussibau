import { RichText } from '@/components/rich-text';
import { Text } from '@/components/text';
import type { PublicHistoryItem } from '../types';

/*
 * The history timeline.
 *
 * A vertical border with a marker per event; year-only events show the year
 * and dated events show the full date. The marker is a shape, not a colour
 * alone, so the order survives a greyscale screenshot (roadmap 6A-82).
 */
export function HistoryTimeline({ items }: { items: PublicHistoryItem[] }) {
    return (
        <ol className="relative space-y-10 border-l border-border pl-6">
            {items.map((item) => (
                <li key={item.id} className="relative">
                    <span
                        aria-hidden="true"
                        className="absolute top-1.5 -left-[calc(1.5rem+5px)] size-2.5 rounded-full border-2 border-primary bg-background"
                    />

                    <Text as="p" variant="label" color="danger">
                        {item.date ?? item.year ?? 'Tahun tidak diketahui'}
                    </Text>

                    <Text as="h3" variant="h4" className="mt-1">
                        {item.title}
                    </Text>

                    {item.image ? (
                        <img
                            src={item.image}
                            alt={item.image_alt ?? ''}
                            loading="lazy"
                            className="mt-4 w-full max-w-md rounded-lg border border-border object-cover"
                        />
                    ) : null}

                    {item.description ? (
                        <RichText
                            html={item.description}
                            className="mt-3 max-w-2xl"
                        />
                    ) : null}
                </li>
            ))}
        </ol>
    );
}

export default HistoryTimeline;
