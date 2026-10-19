import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

/*
 * The parts of an admin list page that are not about what is being listed.
 *
 * These moved out of features/admin/jadwal-misa/components/ because Phase 05
 * gave four more admin features that need the same header, the same toolbar, the
 * same empty state and the same reset link. ARCHITECTURE.md Part B section 1.1
 * draws the line at two consumers, and ArchitectureTest
 * 'a feature does not import another feature' means the alternative — four
 * features reaching into jadwal-misa for a toolbar — was never available.
 *
 * What stayed behind is what is genuinely about that module: StatusBadge reads
 * an is_active flag and NoticeStatusBadge is about schedule notices. Neither
 * means anything outside Jadwal Misa.
 *
 * See docs/ARCHITECTURE.md Part B section 1.1.
 */

export function ListHeader({
    title,
    description,
    count,
    action,
}: {
    title: string;
    description?: string;
    count?: number;
    action?: React.ReactNode;
}) {
    return (
        <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="space-y-1">
                <Text as="h2" variant="h2">
                    {title}
                </Text>
                {description ? (
                    <Text as="p" variant="body-sm" color="muted">
                        {description}
                    </Text>
                ) : null}
                {typeof count === 'number' ? (
                    <Text as="p" variant="body-sm" color="muted">
                        {count} data
                    </Text>
                ) : null}
            </div>
            {action}
        </div>
    );
}

/**
 * A toolbar row: search on the left, filters and add on the right.
 */
export function ListToolbar({
    search,
    onSearch,
    searchPlaceholder = 'Cari',
    children,
    className,
}: {
    search: string;
    onSearch: (value: string) => void;
    searchPlaceholder?: string;
    children?: React.ReactNode;
    className?: string;
}) {
    return (
        <div className={cn('flex flex-wrap items-center gap-3', className)}>
            <input
                type="search"
                value={search}
                onChange={(event) => onSearch(event.target.value)}
                placeholder={searchPlaceholder}
                aria-label={searchPlaceholder}
                className="h-11 min-w-48 flex-1 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
            />
            {children}
        </div>
    );
}

export function ListEmpty({
    title,
    description,
}: {
    title: string;
    description?: string;
}) {
    return (
        <div className="rounded-lg border border-dashed border-border p-10 text-center">
            <Text as="p" variant="heading-sm">
                {title}
            </Text>
            {description ? (
                <Text as="p" variant="body-sm" color="muted" className="mt-1">
                    {description}
                </Text>
            ) : null}
        </div>
    );
}

export function FilterSelect({
    id,
    value,
    onChange,
    options,
    label,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    options: Array<{ value: string; label: string }>;
    label: string;
}) {
    return (
        <select
            id={id}
            value={value}
            aria-label={label}
            onChange={(event) => onChange(event.target.value)}
            className="h-11 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
        >
            {options.map((option) => (
                <option key={option.value} value={option.value}>
                    {option.label}
                </option>
            ))}
        </select>
    );
}

export function ResetFilters({ onReset }: { onReset: () => void }) {
    return (
        <button
            type="button"
            onClick={onReset}
            className="h-11 rounded-md px-3 text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            Reset filter
        </button>
    );
}

/*
 * An active / inactive badge.
 *
 * Moved here from features/admin/jadwal-misa/components/list-parts.tsx when
 * Lokasi became Master Data (D-48). It read as a mass-schedule badge only
 * because the location list was inside that feature; with the location cards in
 * a second feature it has two consumers, and ARCHITECTURE.md Part B section 1.1
 * puts that in components/.
 */
export function StatusBadge({ active }: { active: boolean }) {
    return (
        <Badge variant={active ? 'default' : 'secondary'}>
            {active ? 'Aktif' : 'Nonaktif'}
        </Badge>
    );
}
