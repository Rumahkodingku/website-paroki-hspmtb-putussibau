import { Text } from '@/components/text';

/*
 * The search box and the result count.
 *
 * Client-side on purpose (D-57): an area list is small enough that a round trip
 * per keystroke would cost more than it saves, and the directory deliberately has
 * no pagination or deferred props to defer into.
 *
 * The count is an aria-live status rather than a label: filtering is silent
 * otherwise, and a sighted visitor is the only one who would otherwise notice
 * that the grid just emptied.
 *
 * @see docs/DECISIONS.md D-57
 */
export function AreaSearch({
    value,
    onChange,
    resultCount,
}: {
    value: string;
    onChange: (value: string) => void;
    resultCount: number;
}) {
    return (
        <div className="flex flex-wrap items-center gap-3">
            <div className="min-w-56 flex-1">
                <label htmlFor="area-search" className="sr-only">
                    Cari area
                </label>
                <input
                    id="area-search"
                    type="search"
                    value={value}
                    placeholder="Cari nama stasi atau lingkungan"
                    onChange={(event) => onChange(event.target.value)}
                    className="h-11 w-full rounded-pill border border-hairline bg-background px-4 text-sm focus-visible:border-primary focus-visible:outline-none"
                />
            </div>

            <Text
                as="p"
                variant="caption"
                color="muted"
                role="status"
                aria-live="polite"
            >
                {resultCount} area
            </Text>
        </div>
    );
}
