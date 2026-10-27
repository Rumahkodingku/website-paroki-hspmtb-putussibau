import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, SlidersHorizontal } from 'lucide-react';
import { useEffect, useState } from 'react';
import AlertError from '@/components/alert-error';
import { DataTable, type DataColumn } from '@/components/data-table';
import { DatePicker } from '@/components/date-picker';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    FilterSelect,
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { ListPagination } from '@/components/list-pagination';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { formatWibDateTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import {
    index as auditLogsIndex,
    show as auditLogsShow,
} from '@/routes/audit-logs';
import { AuditResultBadge } from '../components/audit-result-badge';
import type { AuditFilters, AuditLogIndexProps, AuditLogRow } from '../types';

export default function Index({
    events,
    filters,
    options,
}: AuditLogIndexProps) {
    const { displayTimezone, errors } = usePage<{
        displayTimezone: string;
        errors: Record<string, string>;
    }>().props;

    const [open, setOpen] = useState(false);
    const [draft, setDraft] = useState<AuditFilters>(filters);

    useEffect(() => {
        if (open) {
            setDraft(filters);
        }
    }, [open, filters]);

    useEffect(() => {
        if (errors.from || errors.to) {
            setOpen(true);
        }
    }, [errors.from, errors.to]);

    const applyFilter = (next: Partial<AuditFilters>) => {
        router.get(
            auditLogsIndex().url,
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sort: string, direction: 'asc' | 'desc') => {
        router.get(
            auditLogsIndex().url,
            { ...filters, sort, direction, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' ||
        filters.from !== '' ||
        filters.to !== '' ||
        filters.actor !== '' ||
        filters.module !== '' ||
        filters.action !== '' ||
        filters.result !== '';

    const activeFilterCount = [
        filters.actor,
        filters.module,
        filters.action,
        filters.result,
        filters.from,
        filters.to,
    ].filter(Boolean).length;

    const reset = () =>
        applyFilter({
            search: '',
            from: '',
            to: '',
            actor: '',
            module: '',
            action: '',
            result: '',
        });

    const applyDraft = () => {
        setOpen(false);
        applyFilter(draft);
    };

    const resetDraft = () => {
        const next: AuditFilters = {
            search: filters.search,
            from: '',
            to: '',
            actor: '',
            module: '',
            action: '',
            result: '',
            sort: filters.sort,
            direction: filters.direction,
        };

        setDraft(next);
        setOpen(false);
        applyFilter(next);
    };

    const columns: DataColumn<AuditLogRow>[] = [
        {
            id: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'occurred_at',
            accessorKey: 'occurred_at',
            header: 'Waktu',
            sortKey: 'occurred_at',
            cell: ({ row }) => (
                <Text as="span" variant="body-sm">
                    {formatWibDateTime(
                        row.original.occurred_at,
                        displayTimezone,
                    )}
                </Text>
            ),
        },
        {
            id: 'actor',
            header: 'Pengguna',
            sortKey: 'actor',
            cell: ({ row }) => (
                <Text as="span" variant="body-sm">
                    {row.original.actor.label}
                </Text>
            ),
        },
        {
            id: 'activity',
            header: 'Aktivitas',
            sortKey: 'action',
            cell: ({ row }) => (
                <div className="max-w-72 space-y-1">
                    <Text as="p" variant="label">
                        {row.original.action_label}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {row.original.description}
                    </Text>
                </div>
            ),
        },
        {
            id: 'module',
            header: 'Modul',
            sortKey: 'module',
            cell: ({ row }) => (
                <Text as="span" variant="body-sm">
                    {row.original.module_label}
                </Text>
            ),
        },
        {
            id: 'subject',
            header: 'Objek',
            sortKey: 'subject_label',
            cell: ({ row }) => (
                <Text as="span" variant="body-sm">
                    {row.original.subject_label ?? '—'}
                </Text>
            ),
        },
        {
            id: 'result',
            header: 'Hasil',
            sortKey: 'result',
            cell: ({ row }) => (
                <AuditResultBadge
                    result={row.original.result}
                    label={row.original.result_label}
                />
            ),
        },
        {
            id: 'actions',
            header: 'Detail',
            cell: ({ row }) => (
                <Button asChild variant="link" size="sm">
                    <Link href={auditLogsShow(row.original.id)}>
                        <Eye className="size-4" aria-hidden="true" />
                        Lihat
                    </Link>
                </Button>
            ),
        },
    ];

    return (
        <>
            <Head title="Audit Log" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Audit Log"
                    description="Riwayat perubahan dan aktivitas penting pada panel pengelola paroki."
                    count={events.meta.total}
                />

                {errors.from || errors.to ? (
                    <AlertError
                        title="Filter tanggal tidak valid"
                        errors={[errors.from, errors.to].filter(Boolean)}
                    />
                ) : null}

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari deskripsi, objek, atau pengguna"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={() => setOpen(true)}
                    >
                        <SlidersHorizontal
                            className="size-4"
                            aria-hidden="true"
                        />
                        Filter
                        {activeFilterCount > 0 ? (
                            <span className="ml-1 rounded-full bg-primary px-2 py-0.5 text-xs text-primary-foreground">
                                {activeFilterCount}
                            </span>
                        ) : null}
                    </Button>

                    {hasFilter ? <ResetFilters onReset={reset} /> : null}
                </ListToolbar>

                <Dialog open={open} onOpenChange={setOpen}>
                    <DialogContent className="sm:max-w-2xl">
                        <DialogHeader>
                            <DialogTitle>Filter Audit Log</DialogTitle>
                            <DialogDescription>
                                Pilih pengguna, modul, aktivitas, hasil, dan
                                rentang tanggal.
                            </DialogDescription>
                        </DialogHeader>

                        {errors.from || errors.to ? (
                            <AlertError
                                title="Filter tanggal tidak valid"
                                errors={[errors.from, errors.to].filter(
                                    Boolean,
                                )}
                            />
                        ) : null}

                        <div className="grid gap-4 sm:grid-cols-2">
                            <FilterSelect
                                id="filter-actor"
                                label="Filter pengguna"
                                value={draft.actor}
                                onChange={(actor) =>
                                    setDraft((current) => ({
                                        ...current,
                                        actor,
                                    }))
                                }
                                options={[
                                    { value: '', label: 'Semua pengguna' },
                                    ...options.actors,
                                ]}
                            />

                            <FilterSelect
                                id="filter-module"
                                label="Filter modul"
                                value={draft.module}
                                onChange={(module) =>
                                    setDraft((current) => ({
                                        ...current,
                                        module,
                                    }))
                                }
                                options={[
                                    { value: '', label: 'Semua modul' },
                                    ...options.modules,
                                ]}
                            />

                            <FilterSelect
                                id="filter-action"
                                label="Filter aktivitas"
                                value={draft.action}
                                onChange={(action) =>
                                    setDraft((current) => ({
                                        ...current,
                                        action,
                                    }))
                                }
                                options={[
                                    { value: '', label: 'Semua aktivitas' },
                                    ...options.actions,
                                ]}
                            />

                            <FilterSelect
                                id="filter-result"
                                label="Filter hasil"
                                value={draft.result}
                                onChange={(result) =>
                                    setDraft((current) => ({
                                        ...current,
                                        result,
                                    }))
                                }
                                options={[
                                    { value: '', label: 'Semua hasil' },
                                    ...options.results,
                                ]}
                            />

                            <DatePicker
                                id="filter-from"
                                value={draft.from}
                                onChange={(from) =>
                                    setDraft((current) => ({
                                        ...current,
                                        from,
                                    }))
                                }
                                placeholder="Dari tanggal"
                                className="h-11 w-full"
                            />

                            <DatePicker
                                id="filter-to"
                                value={draft.to}
                                onChange={(to) =>
                                    setDraft((current) => ({
                                        ...current,
                                        to,
                                    }))
                                }
                                placeholder="Sampai tanggal"
                                className="h-11 w-full"
                            />
                        </div>

                        <DialogFooter className="mt-6">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={resetDraft}
                            >
                                Reset
                            </Button>
                            <DialogClose asChild>
                                <Button type="button" variant="outline">
                                    Batal
                                </Button>
                            </DialogClose>
                            <Button type="button" onClick={applyDraft}>
                                Terapkan
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>

                <DataTable
                    columns={columns}
                    rows={events.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? 'Tidak ada aktivitas yang cocok dengan filter.'
                                    : 'Belum ada aktivitas audit.'
                            }
                            description={
                                hasFilter
                                    ? 'Coba reset filter untuk melihat seluruh aktivitas.'
                                    : 'Aktivitas administratif akan tampil di sini setelah ada perubahan.'
                            }
                        />
                    }
                />

                <ListPagination
                    meta={events.meta}
                    previous={events.links.prev}
                    next={events.links.next}
                />
            </div>
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Audit Log', href: auditLogsIndex() },
    ],
};
