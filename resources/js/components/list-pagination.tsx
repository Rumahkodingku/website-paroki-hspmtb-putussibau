import { Link, router } from '@inertiajs/react';
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from 'lucide-react';
import { useState } from 'react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

type Props = {
    meta: Paginated<unknown>['meta'];
    previous: string | null;
    next: string | null;
    className?: string;
};

const PER_PAGE_OPTIONS = [10, 15, 25, 50];

function navigate(params: Record<string, string | number | null>) {
    const url = new URL(window.location.href);

    for (const [key, value] of Object.entries(params)) {
        if (value === null || value === undefined || value === '') {
            url.searchParams.delete(key);
        } else {
            url.searchParams.set(key, String(value));
        }
    }

    router.get(
        url.pathname + url.search,
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

export function ListPagination({ meta, previous, next, className }: Props) {
    const [jumpPage, setJumpPage] = useState(String(meta.current_page));

    if (meta.last_page <= 1) {
        return null;
    }

    const from = meta.from ?? 0;
    const to = meta.to ?? 0;

    return (
        <nav
            aria-label="Paginasi"
            className={cn('flex flex-col gap-3 pt-2', className)}
        >
            <div className="flex flex-col items-center gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Text
                    as="p"
                    variant="body-sm"
                    color="muted"
                    className="text-center sm:text-left"
                >
                    Menampilkan {from}-{to} dari {meta.total} data
                </Text>

                <div className="flex w-full flex-col items-center gap-2 sm:w-auto sm:flex-row sm:justify-end">
                    <div className="flex items-center gap-2">
                        <Text as="span" variant="body-sm" color="muted">
                            Per halaman
                        </Text>
                        <Select
                            value={String(meta.per_page)}
                            onValueChange={(value) =>
                                navigate({ per_page: value, page: 1 })
                            }
                        >
                            <SelectTrigger
                                aria-label="Jumlah data per halaman"
                                className="h-9 w-20 border-border bg-background shadow-none"
                            >
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent side="top" align="end">
                                {PER_PAGE_OPTIONS.map((option) => (
                                    <SelectItem
                                        key={option}
                                        value={String(option)}
                                    >
                                        {option}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <div className="flex items-center gap-1">
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-9"
                            disabled={meta.current_page <= 1}
                            aria-label="Halaman pertama"
                            onClick={() => navigate({ page: 1 })}
                        >
                            <ChevronsLeft
                                className="size-4"
                                aria-hidden="true"
                            />
                        </Button>

                        {previous ? (
                            <Link
                                href={previous}
                                preserveScroll
                                preserveState
                                aria-label="Halaman sebelumnya"
                                className="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-border hover:bg-accent focus-visible:border-primary"
                            >
                                <ChevronLeft
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </Link>
                        ) : (
                            <span
                                aria-hidden="true"
                                className="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-border text-muted-foreground opacity-50"
                            >
                                <ChevronLeft className="size-4" />
                            </span>
                        )}

                        <div className="hidden items-center gap-1 sm:flex">
                            {meta.links
                                .filter((link) => {
                                    const label = link.label
                                        .trim()
                                        .toLowerCase();
                                    const isPrevNextLabel =
                                        label.includes('sebelumnya') ||
                                        label.includes('berikutnya');

                                    if (isPrevNextLabel) {
                                        return false;
                                    }

                                    if (
                                        link.url !== null &&
                                        (link.url === previous ||
                                            link.url === next)
                                    ) {
                                        return false;
                                    }

                                    return true;
                                })
                                .map((link) =>
                                    link.url === null ? (
                                        <span
                                            key={`${link.label}-gap`}
                                            aria-hidden="true"
                                            className="px-2 text-sm text-muted-foreground"
                                        >
                                            {link.label}
                                        </span>
                                    ) : (
                                        <Link
                                            key={link.label}
                                            href={link.url}
                                            preserveScroll
                                            preserveState
                                            aria-current={
                                                link.active ? 'page' : undefined
                                            }
                                            className={cn(
                                                'inline-flex h-9 min-w-9 items-center justify-center rounded-md border px-3 text-sm',
                                                link.active
                                                    ? 'border-primary bg-primary text-primary-foreground'
                                                    : 'border-border hover:bg-accent',
                                            )}
                                        >
                                            {link.label}
                                        </Link>
                                    ),
                                )}
                        </div>

                        {next ? (
                            <Link
                                href={next}
                                preserveScroll
                                preserveState
                                aria-label="Halaman berikutnya"
                                className="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-border hover:bg-accent focus-visible:border-primary"
                            >
                                <ChevronRight
                                    aria-hidden="true"
                                    className="size-4"
                                />
                            </Link>
                        ) : (
                            <span
                                aria-hidden="true"
                                className="inline-flex size-9 shrink-0 items-center justify-center rounded-md border border-border text-muted-foreground opacity-50"
                            >
                                <ChevronRight className="size-4" />
                            </span>
                        )}

                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            className="size-9"
                            disabled={meta.current_page >= meta.last_page}
                            aria-label="Halaman terakhir"
                            onClick={() => navigate({ page: meta.last_page })}
                        >
                            <ChevronsRight
                                className="size-4"
                                aria-hidden="true"
                            />
                        </Button>
                    </div>

                    <div className="flex items-center gap-2">
                        <Text as="span" variant="body-sm" color="muted">
                            Lompat ke
                        </Text>
                        <Input
                            type="number"
                            min={1}
                            max={meta.last_page}
                            value={jumpPage}
                            onChange={(
                                event: React.ChangeEvent<HTMLInputElement>,
                            ) => setJumpPage(event.target.value)}
                            onKeyDown={(
                                event: React.KeyboardEvent<HTMLInputElement>,
                            ) => {
                                if (event.key === 'Enter') {
                                    const page = Number(jumpPage);
                                    if (
                                        Number.isInteger(page) &&
                                        page >= 1 &&
                                        page <= meta.last_page
                                    ) {
                                        navigate({ page });
                                    }
                                }
                            }}
                            aria-label="Nomor halaman tujuan"
                            className="h-9 w-16 border-border bg-background shadow-none"
                        />
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            className="h-9"
                            onClick={() => {
                                const page = Number(jumpPage);
                                if (
                                    Number.isInteger(page) &&
                                    page >= 1 &&
                                    page <= meta.last_page
                                ) {
                                    navigate({ page });
                                }
                            }}
                        >
                            Go
                        </Button>
                    </div>
                </div>
            </div>
        </nav>
    );
}
