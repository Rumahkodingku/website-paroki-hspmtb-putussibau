import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { DataTable } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { RowActions } from '@/components/row-actions';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import {
    create as createArea,
    destroy as destroyArea,
    edit as editArea,
    index as areasIndex,
} from '@/routes/areas';
import { areaColumns } from '../components/area-columns';
import type { AreaRow, AreaTypeOption } from '../types';

type Props = {
    areas: AreaRow[];
    filters: {
        search: string;
        type: string;
        status: string;
        sort: string;
        direction: string;
    };
    copy: Record<string, string>;
    empty: Record<string, string>;
    areaTypes: AreaTypeOption[];
};

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'active', label: 'Aktif' },
    { value: 'inactive', label: 'Nonaktif' },
];

export default function Index({
    areas,
    filters,
    copy,
    empty,
    areaTypes,
}: Props) {
    const [deleting, setDeleting] = useState<AreaRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            areasIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sortKey: string, direction: 'asc' | 'desc') => {
        router.get(
            areasIndex().url,
            { ...filters, sort: sortKey, direction },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' || filters.type !== '' || filters.status !== '';

    const reset = () =>
        applyFilter({
            search: '',
            type: '',
            status: '',
            sort: '',
            direction: '',
        });

    const nameById = new Map(areas.map((area) => [area.id, area.name]));

    return (
        <>
            <Head title="Area" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Area"
                    description={
                        copy.area_description ??
                        'Hierarki stasi, wilayah, dan lingkungan serta pengurusnya.'
                    }
                    action={
                        <Button asChild>
                            <Link href={createArea()}>Tambah Area</Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari nama stasi/lingkungan"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <select
                        aria-label="Filter tipe"
                        value={filters.type}
                        onChange={(event) =>
                            applyFilter({ type: event.target.value })
                        }
                        className="h-11 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        <option value="">Semua tipe</option>
                        {areaTypes.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>

                    <select
                        aria-label="Filter status"
                        value={filters.status}
                        onChange={(event) =>
                            applyFilter({ status: event.target.value })
                        }
                        className="h-11 rounded-md border border-border bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        {STATUS_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>

                    {hasFilter || filters.sort !== '' ? (
                        <ResetFilters onReset={reset} />
                    ) : null}
                </ListToolbar>

                <DataTable
                    columns={areaColumns(nameById, (row) => (
                        <RowActions
                            canUpdate={row.can.update}
                            canDelete={row.can.delete}
                            updateHref={editArea(row.id)}
                            onDelete={() => setDeleting(row)}
                        />
                    ))}
                    rows={areas}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title={
                                hasFilter
                                    ? (empty.filtered ??
                                      'Tidak ada area yang cocok dengan filter.')
                                    : (empty.areas ??
                                      'Belum ada stasi, wilayah, atau lingkungan.')
                            }
                            description={
                                hasFilter
                                    ? 'Coba reset filter untuk melihat seluruh area.'
                                    : 'Tambahkan area pertama untuk mulai.'
                            }
                        />
                    }
                />
            </div>

            {deleting ? (
                <DeleteDialog
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    itemLabel={`Area "${deleting.name}"`}
                    consequences={[]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyArea(deleting.id), {
                            preserveScroll: true,
                            onFinish: () => {
                                setSubmitting(false);
                                setDeleting(null);
                            },
                        });
                    }}
                />
            ) : null}
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Area', href: areasIndex() },
    ],
};
