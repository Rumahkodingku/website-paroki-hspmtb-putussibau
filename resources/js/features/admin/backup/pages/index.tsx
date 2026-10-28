import { Head, Link, router, usePage } from '@inertiajs/react';
import { formatBytes } from '@uploadcn/core';
import { AlertTriangle, DatabaseBackup, Settings2, Upload } from 'lucide-react';
import { useEffect, useState } from 'react';
import { DataTable, type DataColumn } from '@/components/data-table';
import { ListEmpty, ListHeader } from '@/components/list-parts';
import { ListPagination } from '@/components/list-pagination';
import { Text } from '@/components/text';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { formatWibDateTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import { destroy, index as backupsIndex, show } from '@/routes/backups';
import type { SharedProps } from '@/types';
import { BackupFilters } from '../components/backup-filters';
import { BackupRowActions } from '../components/backup-row-actions';
import { BackupStatusBadge } from '../components/backup-status-badge';
import { BackupSummary } from '../components/backup-summary';
import { edit as backupSettingsEdit } from '@/routes/backups/settings';
import { BackupTypeBadge } from '../components/backup-type-badge';
import { CreateBackupDialog } from '../components/create-backup-dialog';
import { UploadBackupDialog } from '../components/upload-backup-dialog';
import type {
    BackupFilters as Filters,
    BackupIndexProps,
    BackupRow,
} from '../types';

export default function Index({
    backups,
    filters,
    summary,
    readiness,
}: BackupIndexProps) {
    const { displayTimezone, abilities } = usePage<SharedProps>().props;
    const [createOpen, setCreateOpen] = useState(false);
    const [uploadOpen, setUploadOpen] = useState(false);
    const [deleting, setDeleting] = useState<BackupRow | null>(null);

    const hasActive =
        summary.active_restore !== null ||
        backups.data.some((backup) =>
            ['queued', 'running', 'validating'].includes(backup.status),
        );

    useEffect(() => {
        if (!hasActive) {
            return;
        }

        const interval = window.setInterval(() => {
            router.reload({
                only: ['backups', 'summary', 'readiness'],
            });
        }, 5000);

        return () => window.clearInterval(interval);
    }, [hasActive]);

    const applyFilter = (next: Partial<Filters>) => {
        router.get(
            backupsIndex.url(),
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const reset = () => {
        applyFilter({
            search: '',
            type: '',
            source: '',
            status: '',
            validasi: '',
            dari: '',
            sampai: '',
        });
    };

    const sortBy = (sort: string, direction: 'asc' | 'desc') => {
        router.get(
            backupsIndex.url(),
            { ...filters, sort, direction, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const confirmDelete = () => {
        if (deleting === null) {
            return;
        }

        router.delete(destroy.url(deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    const columns: DataColumn<BackupRow>[] = [
        {
            id: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'backup',
            header: 'Backup',
            cell: ({ row }) => (
                <div className="space-y-1">
                    <div className="flex flex-wrap items-center gap-2">
                        <BackupTypeBadge
                            type={row.original.type}
                            label={row.original.type_label}
                        />
                        <BackupStatusBadge
                            status={row.original.status}
                            label={row.original.status_label}
                        />
                    </div>
                    <Text as="p" variant="body-sm" color="muted">
                        {row.original.source_label}
                        {row.original.created_by_label
                            ? ` · ${row.original.created_by_label}`
                            : ''}
                    </Text>
                </div>
            ),
        },
        {
            id: 'created_at',
            header: 'Waktu',
            sortKey: 'created_at',
            cell: ({ row }) => (
                <Text as="span" variant="body-sm">
                    {row.original.created_at
                        ? formatWibDateTime(
                              row.original.created_at,
                              displayTimezone,
                          )
                        : '-'}
                </Text>
            ),
        },
        {
            id: 'file_size',
            header: 'Ukuran',
            sortKey: 'file_size',
            cell: ({ row }) => (
                <Text as="span" variant="body-sm">
                    {row.original.file_size === null
                        ? '-'
                        : formatBytes(row.original.file_size)}
                </Text>
            ),
        },
        {
            id: 'validation',
            header: 'Validasi',
            cell: ({ row }) => (
                <Text as="span" variant="body-sm">
                    {row.original.validation_label}
                </Text>
            ),
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => (
                <BackupRowActions
                    backup={row.original}
                    onDelete={() => setDeleting(row.original)}
                />
            ),
        },
    ];

    return (
        <>
            <Head title="Backup & Pemulihan" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Backup & Pemulihan"
                    description="Buat, unduh, unggah, dan pulihkan backup aplikasi."
                    count={backups.meta.total}
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            {abilities['backups.settings'] ? (
                                <Button variant="outline" asChild>
                                    <Link href={backupSettingsEdit.url()}>
                                        <Settings2
                                            className="size-4"
                                            aria-hidden="true"
                                        />
                                        Pengaturan
                                    </Link>
                                </Button>
                            ) : null}
                            {abilities['backups.upload'] ? (
                                <Button
                                    variant="outline"
                                    onClick={() => setUploadOpen(true)}
                                >
                                    <Upload
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Unggah
                                </Button>
                            ) : null}
                            {abilities['backups.create'] ? (
                                <Button onClick={() => setCreateOpen(true)}>
                                    <DatabaseBackup
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Buat backup
                                </Button>
                            ) : null}
                        </div>
                    }
                />

                <BackupSummary
                    summary={summary}
                    readiness={readiness}
                    timezone={displayTimezone}
                />

                {summary.active_restore ? (
                    <Alert>
                        <AlertTriangle aria-hidden="true" />
                        <AlertTitle>
                            Pemulihan {summary.active_restore.status_label}
                        </AlertTitle>
                        <AlertDescription>
                            Operasi pemulihan sedang berjalan. Perubahan data
                            ditahan sampai selesai.{' '}
                            <Link
                                href={show.url(
                                    summary.active_restore.backup_id,
                                )}
                                className="underline underline-offset-4"
                            >
                                Lihat detail pemulihan
                            </Link>
                        </AlertDescription>
                    </Alert>
                ) : null}

                <BackupFilters
                    filters={filters}
                    onChange={applyFilter}
                    onReset={reset}
                />

                <DataTable
                    columns={columns}
                    rows={backups.data}
                    sort={filters.sort}
                    direction={filters.direction}
                    onSort={sortBy}
                    rowId={(row) => row.id}
                    empty={
                        <ListEmpty
                            title="Belum ada backup"
                            description="Buat backup database atau backup lengkap untuk mulai mengisi riwayat."
                        />
                    }
                />

                <ListPagination
                    meta={backups.meta}
                    previous={backups.links.prev}
                    next={backups.links.next}
                />
            </div>

            <CreateBackupDialog
                open={createOpen}
                onOpenChange={setCreateOpen}
            />

            <UploadBackupDialog
                open={uploadOpen}
                onOpenChange={setUploadOpen}
                readiness={readiness}
            />

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setDeleting(null);
                    }
                }}
                title="Hapus backup"
                destructive
                confirmLabel="Hapus"
                description={
                    deleting
                        ? `Backup ${deleting.type_label} akan dihapus dari penyimpanan dan riwayat. Tindakan ini tidak dapat dibatalkan.`
                        : ''
                }
                onConfirm={confirmDelete}
            />
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Backup & Pemulihan', href: backupsIndex() },
    ],
};
