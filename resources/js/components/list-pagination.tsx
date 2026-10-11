import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import { Text } from '@/components/text';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

type Props = {
    meta: Paginated<unknown>['meta'];
    previous: string | null;
    next: string | null;
    className?: string;
};

export function ListPagination({ meta, previous, next, className }: Props) {
    if (meta.last_page <= 1) {
        return null;
    }

    return (
        <nav
            aria-label="Paginasi"
            className={cn(
                'flex flex-wrap items-center justify-between gap-3 pt-2',
                className,
            )}
        >
            <Text as="p" variant="caption" color="muted">
                Halaman {meta.current_page} dari {meta.last_page} · {meta.total}{' '}
                data
            </Text>

            <div className="flex items-center gap-1">
                {previous ? (
                    <Link
                        href={previous}
                        preserveScroll
                        preserveState
                        aria-label="Halaman sebelumnya"
                        className="inline-flex h-11 w-11 items-center justify-center rounded-md border border-hairline hover:bg-accent focus-visible:border-primary"
                    >
                        <ChevronLeft aria-hidden="true" className="size-4" />
                    </Link>
                ) : null}

                {meta.links.map((link) =>
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
                            aria-current={link.active ? 'page' : undefined}
                            className={cn(
                                'inline-flex h-11 min-w-11 items-center justify-center rounded-md border px-3 text-sm',
                                link.active
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-hairline hover:bg-accent',
                            )}
                        >
                            {link.label}
                        </Link>
                    ),
                )}

                {next ? (
                    <Link
                        href={next}
                        preserveScroll
                        preserveState
                        aria-label="Halaman berikutnya"
                        className="inline-flex h-11 w-11 items-center justify-center rounded-md border border-hairline hover:bg-accent focus-visible:border-primary"
                    >
                        <ChevronRight aria-hidden="true" className="size-4" />
                    </Link>
                ) : null}
            </div>
        </nav>
    );
}
