import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as massSchedulesIndex } from '@/routes/mass-schedules';
import {
    create as createScheduleNotice,
    destroy as destroyScheduleNotice,
    edit as editScheduleNotice,
    index as scheduleNoticesIndex,
} from '@/routes/schedule-notices';
import { DataTable } from '@/components/data-table';
import { scheduleNoticeColumns } from '../../components/columns';
import { DeleteDialog } from '@/components/delete-dialog';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { RowActions } from '@/components/row-actions';
import { ScheduleNoticeCards } from './components/schedule-notice-cards';
import { ListPagination } from '@/components/list-pagination';
import type { FormMeta, ScheduleNoticeRow } from '../../types';
import type { Paginated } from '@/types';

type Props = {
    notices: Paginated<ScheduleNoticeRow>;
    filters: {
        search: string;
        status: string;
        sort: string;
        direction: string;
    };
} & Pick<FormMeta, 'noticeScopes'>;

const STATUS_OPTIONS = [
    { value: '', label: 'Semua status' },
    { value: 'visible', label: 'Sedang tampil' },
    { value: 'scheduled', label: 'Terjadwal' },
    { value: 'draft', label: 'Nonaktif' },
    { value: 'expired', label: 'Kedaluwarsa' },
];

export default function Index({ notices, filters, noticeScopes }: Props) {
    const { displayTimezone } = usePage().props;
    const [deleting, setDeleting] = useState<ScheduleNoticeRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            scheduleNoticesIndex().url,
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sortKey: string, direction: 'asc' | 'desc') => {
        router.get(
            scheduleNoticesIndex().url,
            { ...filters, sort: sortKey, direction },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter = filters.search !== '' || filters.status !== '';
    const generalLabel = noticeScopes?.general ?? 'Umum';

    const scopeLabel = (notice: ScheduleNoticeRow): string => {
        if (notice.is_general) {
            return generalLabel;
        }

        if (notice.mass_schedule) {
            return `Jadwal rutin — ${notice.mass_schedule.name}`;
        }

        if (notice.special_mass) {
            return `Misa khusus — ${notice.special_mass.title}`;
        }

        return notice.location
            ? `Lokasi — ${notice.location.name}`
            : generalLabel;
    };

    return (
        <>
            <Head title="Pemberitahuan" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Pemberitahuan"
                    description="Pengumuman perubahan jadwal yang tampil di halaman publik."
                    action={
                        <Button asChild>
                            <Link href={createScheduleNotice()}>
                                Tambah pemberitahuan
                            </Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder="Cari judul atau isi pemberitahuan"
                    onSearch={(search) => applyFilter({ search })}
                >
                    <select
                        aria-label="Filter status"
                        value={filters.status}
                        onChange={(event) =>
                            applyFilter({ status: event.target.value })
                        }
                        className="h-11 rounded-md border border-hairline bg-background px-3 text-sm focus-visible:border-primary"
                    >
                        {STATUS_OPTIONS.map((option) => (
                            <option key={option.value} value={option.value}>
                                {option.label}
                            </option>
                        ))}
                    </select>

                    {hasFilter || filters.sort !== '' ? (
                        <ResetFilters
                            onReset={() =>
                                applyFilter({
                                    search: '',
                                    status: '',
                                    sort: '',
                                    direction: '',
                                })
                            }
                        />
                    ) : null}
                </ListToolbar>

                <ScheduleNoticeCards
                    rows={notices.data}
                    displayTimezone={displayTimezone}
                    scopeLabel={scopeLabel}
                />

                <div className="hidden md:block">
                    <DataTable
                        columns={scheduleNoticeColumns(generalLabel, (row) => (
                            <RowActions
                                canUpdate={row.can.update}
                                canDelete={row.can.delete}
                                updateHref={editScheduleNotice(row.id)}
                                onDelete={() => setDeleting(row)}
                            />
                        ))}
                        rows={notices.data}
                        sort={filters.sort}
                        direction={filters.direction}
                        onSort={sortBy}
                        rowId={(row) => row.id}
                        empty={
                            <ListEmpty
                                title={
                                    hasFilter
                                        ? 'Tidak ada pemberitahuan untuk filter ini.'
                                        : 'Belum ada pemberitahuan.'
                                }
                                description={
                                    hasFilter
                                        ? 'Coba reset filter untuk melihat seluruh pemberitahuan.'
                                        : 'Tambahkan pemberitahuan pertama untuk mulai.'
                                }
                            />
                        }
                    />
                </div>

                <ListPagination
                    meta={notices.meta}
                    previous={notices.links.prev}
                    next={notices.links.next}
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
                    itemLabel={`Pemberitahuan "${deleting.title}"`}
                    consequences={[]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyScheduleNotice(deleting.id), {
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
        { title: 'Jadwal Misa', href: massSchedulesIndex() },
        { title: 'Pemberitahuan' },
    ],
};
