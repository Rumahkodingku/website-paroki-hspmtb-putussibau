import { useEffect, useRef, useState } from 'react';
import { CalendarDays, X } from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Calendar } from '@/components/ui/calendar';
import { Input } from '@/components/ui/input';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { wibMonthLabel } from '@/lib/datetime';
import { SEARCH_DEBOUNCE_MS } from '@/lib/search-debouce';
import { cn } from '@/lib/utils';

const EMPTY_VALUE = '__empty__';

function toSelectValue(value: string): string {
    return value === '' ? EMPTY_VALUE : value;
}

function fromSelectValue(value: string): string {
    return value === EMPTY_VALUE ? '' : value;
}

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
    const [value, setValue] = useState(search);
    const onSearchRef = useRef(onSearch);

    useEffect(() => {
        onSearchRef.current = onSearch;
    });

    useEffect(() => {
        setValue(search);
    }, [search]);

    useEffect(() => {
        if (value === search) {
            return;
        }

        const timeout = window.setTimeout(() => {
            onSearchRef.current(value.trim());
        }, SEARCH_DEBOUNCE_MS);

        return () => window.clearTimeout(timeout);
    }, [value, search]);

    return (
        <div
            className={cn(
                'relative flex flex-wrap items-center gap-3',
                className,
            )}
        >
            <Input
                type="search"
                value={value}
                onChange={(event: React.ChangeEvent<HTMLInputElement>) =>
                    setValue(event.target.value)
                }
                placeholder={searchPlaceholder}
                aria-label={searchPlaceholder}
                className="h-11 min-w-48 flex-1 border-border bg-background pr-10 shadow-none focus-visible:border-primary"
            />

            {value !== '' ? (
                <button
                    type="button"
                    onClick={() => {
                        setValue('');
                        onSearchRef.current('');
                    }}
                    aria-label="Hapus pencarian"
                    className="absolute top-1/2 right-3 flex size-8 -translate-y-1/2 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-muted hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                >
                    <X className="size-4" aria-hidden="true" />
                </button>
            ) : null}

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
        <Select
            value={toSelectValue(value)}
            onValueChange={(next) => onChange(fromSelectValue(next))}
        >
            <SelectTrigger
                id={id}
                aria-label={label}
                className="h-11 w-auto cursor-pointer rounded-md border-border bg-background py-6 shadow-none focus-visible:border-primary"
            >
                <SelectValue />
            </SelectTrigger>

            <SelectContent>
                {options.map((option) => (
                    <SelectItem
                        key={option.value}
                        value={toSelectValue(option.value)}
                        className="cursor-pointer rounded-md py-2.5"
                    >
                        {option.label}
                    </SelectItem>
                ))}
            </SelectContent>
        </Select>
    );
}

export function FilterMonth({
    id,
    value,
    onChange,
    label,
}: {
    id: string;
    value: string;
    onChange: (value: string) => void;
    label: string;
}) {
    const [open, setOpen] = useState(false);
    const [year = '', month = ''] = value.split('-');
    const selected =
        year !== '' && month !== ''
            ? new Date(Number(year), Number(month) - 1, 1, 12)
            : undefined;

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    type="button"
                    variant="secondary"
                    aria-label={label}
                    className="h-11 min-w-40 cursor-pointer justify-start gap-2 rounded-md border-border bg-background text-sm font-normal text-foreground shadow-none focus-visible:border-primary"
                >
                    <CalendarDays
                        className="size-4 text-muted-foreground"
                        aria-hidden="true"
                    />
                    {value === '' ? 'Semua bulan' : wibMonthLabel(value)}
                </Button>
            </PopoverTrigger>

            <PopoverContent align="start" className="w-auto p-0">
                <Calendar
                    mode="single"
                    selected={selected}
                    defaultMonth={selected}
                    captionLayout="dropdown"
                    onSelect={(date) => {
                        if (!date) {
                            return;
                        }

                        onChange(
                            `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`,
                        );
                        setOpen(false);
                    }}
                />

                {value !== '' ? (
                    <div className="border-t p-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            className="w-full"
                            onClick={() => {
                                onChange('');
                                setOpen(false);
                            }}
                        >
                            Hapus filter bulan
                        </Button>
                    </div>
                ) : null}
            </PopoverContent>
        </Popover>
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
