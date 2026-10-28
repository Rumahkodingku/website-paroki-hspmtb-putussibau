import { Head, Link, router, usePage } from '@inertiajs/react';
import { formatBytes } from '@uploadcn/core';
import {
    AlertTriangle,
    ArrowLeft,
    Download,
    RotateCcw,
    ShieldCheck,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { ListEmpty } from '@/components/list-parts';
import { Text } from '@/components/text';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatWibDateTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import { destroy, download, index, show } from '@/routes/backups';
import type { SharedProps } from '@/types';
import { BackupStatusBadge } from '../components/backup-status-badge';
import { BackupTypeBadge } from '../components/backup-type-badge';
import { ConfirmRestoreDialog } from '../components/confirm-restore-dialog';
import { PrepareRestoreDialog } from '../components/prepare-restore-dialog';
import type { BackupShowProps } from '../types';

function DetailRow({
    label,
    value,
    mono = false,
}: {
    label: string;
    value: string;
    mono?: boolean;
}) {
    return (
        <div className="flex items-start justify-between gap-4">
            <Text
                as="dt"
                variant="body-sm"
                color="muted"
                className="shrink-0 whitespace-nowrap"
            >
                {label}
            </Text>
            <Text
                as="dd"
                variant="body-sm"
                className={`text-right break-all ${mono ? 'font-mono text-xs' : ''}`}
            >
                {value}
            </Text>
        </div>
    );
}

export default function Show({ backup }: BackupShowProps) {
    const { displayTimezone, abilities } = usePage<SharedProps>().props;
    const [prepareOpen, setPrepareOpen] = useState(false);
    const [deleting, setDeleting] = useState(false);
    const [confirmOpen, setConfirmOpen] = useState(false);

    const readyRestore =
        backup.restores.find(
            (item) => item.status === 'ready' && item.can_confirm,
        ) ?? null;

    const confirmDelete = () => {
        router.delete(destroy.url(backup.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(false),
        });
    };

    return (
        <>
            <Head title={`Backup ${backup.uuid}`} />

            <div className="space-y-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="-ml-3"
                        >
                            <Link href={index.url()}>
                                <ArrowLeft
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Kembali ke Backup
                            </Link>
                        </Button>

                        <div className="flex flex-wrap items-center gap-3">
                            <Text as="h2" variant="h2">
                                Backup {backup.type_label}
                            </Text>
                            <BackupTypeBadge
                                type={backup.type}
                                label={backup.type_label}
                            />
                            <BackupStatusBadge
                                status={backup.status}
                                label={backup.status_label}
                            />
                            <BackupStatusBadge
                                status={backup.validation_status}
                                label={backup.validation_label}
                            />
                        </div>

                        <Text as="p" variant="body-sm" color="muted">
                            Dibuat{' '}
                            {backup.created_at
                                ? formatWibDateTime(
                                      backup.created_at,
                                      displayTimezone,
                                  )
                                : '-'}
                        </Text>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {backup.can.download ? (
                            <Button variant="outline" asChild>
                                <a href={download.url(backup.id)}>
                                    <Download
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                    Unduh
                                </a>
                            </Button>
                        ) : null}

                        {backup.can.restore && abilities['backups.restore'] ? (
                            <Button onClick={() => setPrepareOpen(true)}>
                                <RotateCcw
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Siapkan Pemulihan
                            </Button>
                        ) : null}

                        {backup.can.delete && abilities['backups.delete'] ? (
                            <Button
                                variant="outline"
                                onClick={() => setDeleting(true)}
                            >
                                <Trash2 className="size-4" aria-hidden="true" />
                                Hapus
                            </Button>
                        ) : null}
                    </div>
                </div>

                {backup.failure_message ? (
                    <Alert variant="destructive">
                        <AlertTriangle aria-hidden="true" />
                        <AlertTitle>Backup tidak berhasil</AlertTitle>
                        <AlertDescription>
                            {backup.failure_message}
                        </AlertDescription>
                    </Alert>
                ) : null}

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Metadata</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="space-y-3">
                                <DetailRow
                                    label="UUID"
                                    value={backup.uuid}
                                    mono
                                />
                                <Separator />
                                <DetailRow
                                    label="Sumber"
                                    value={backup.source_label}
                                />
                                <Separator />
                                <DetailRow
                                    label="Ukuran"
                                    value={
                                        backup.file_size === null
                                            ? '-'
                                            : formatBytes(backup.file_size)
                                    }
                                />
                                <Separator />
                                <DetailRow
                                    label="Durasi"
                                    value={
                                        backup.duration_ms === null
                                            ? '-'
                                            : `${Math.round(backup.duration_ms / 1000)} detik`
                                    }
                                />
                                <Separator />
                                <DetailRow
                                    label="Pembuat"
                                    value={backup.created_by_label ?? '-'}
                                />
                                {backup.note ? (
                                    <>
                                        <Separator />
                                        <DetailRow
                                            label="Catatan"
                                            value={backup.note}
                                        />
                                    </>
                                ) : null}
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Integritas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="space-y-3">
                                <DetailRow
                                    label="SHA-256"
                                    value={backup.checksum ?? '-'}
                                    mono
                                />
                                <Separator />
                                <DetailRow
                                    label="Format"
                                    value={backup.format_version ?? '-'}
                                />
                                <Separator />
                                <DetailRow
                                    label="Divalidasi"
                                    value={
                                        backup.validated_at
                                            ? formatWibDateTime(
                                                  backup.validated_at,
                                                  displayTimezone,
                                              )
                                            : '-'
                                    }
                                />
                                <Separator />
                                <DetailRow
                                    label="File"
                                    value={
                                        backup.file_missing_at
                                            ? 'Tidak tersedia'
                                            : 'Tersedia'
                                    }
                                />
                            </dl>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Riwayat pemulihan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {backup.restores.length === 0 ? (
                            <ListEmpty
                                title="Belum ada pemulihan"
                                description="Riwayat pemulihan untuk backup ini akan tampil di sini."
                            />
                        ) : (
                            <div className="space-y-4">
                                {backup.restores.map((item, index) => (
                                    <div key={item.id} className="space-y-3">
                                        <div className="flex flex-wrap items-center justify-between gap-3">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <BackupStatusBadge
                                                    status={item.status}
                                                    label={item.status_label}
                                                />
                                                <Text
                                                    as="span"
                                                    variant="body-sm"
                                                    color="muted"
                                                >
                                                    {item.created_at
                                                        ? formatWibDateTime(
                                                              item.created_at,
                                                              displayTimezone,
                                                          )
                                                        : '-'}
                                                </Text>
                                            </div>

                                            {item.status === 'ready' &&
                                            item.can_confirm ? (
                                                <Button
                                                    size="sm"
                                                    onClick={() =>
                                                        setConfirmOpen(true)
                                                    }
                                                >
                                                    <ShieldCheck
                                                        className="size-4"
                                                        aria-hidden="true"
                                                    />
                                                    Konfirmasi
                                                </Button>
                                            ) : null}
                                        </div>

                                        {item.failure_message ? (
                                            <Alert variant="destructive">
                                                <AlertTriangle aria-hidden="true" />
                                                <AlertDescription>
                                                    {item.failure_message}
                                                </AlertDescription>
                                            </Alert>
                                        ) : null}

                                        <div className="flex flex-wrap gap-x-4 gap-y-1">
                                            {item.safety_backup_id ? (
                                                <Link
                                                    href={show.url(
                                                        item.safety_backup_id,
                                                    )}
                                                    className="text-body-sm text-primary-on-surface underline-offset-4 hover:underline"
                                                >
                                                    Lihat safety backup
                                                </Link>
                                            ) : null}

                                            {item.expires_at ? (
                                                <Text
                                                    as="span"
                                                    variant="body-sm"
                                                    color="muted"
                                                >
                                                    Berlaku sampai{' '}
                                                    {formatWibDateTime(
                                                        item.expires_at,
                                                        displayTimezone,
                                                    )}
                                                </Text>
                                            ) : null}
                                        </div>

                                        {item.verification ? (
                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                color="muted"
                                            >
                                                Verifikasi:{' '}
                                                {Object.entries(
                                                    item.verification,
                                                )
                                                    .map(
                                                        ([key, value]) =>
                                                            `${key}: ${value ? 'lulus' : 'gagal'}`,
                                                    )
                                                    .join(', ')}
                                            </Text>
                                        ) : null}

                                        {index < backup.restores.length - 1 ? (
                                            <Separator />
                                        ) : null}
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            <PrepareRestoreDialog
                backup={backup}
                open={prepareOpen}
                onOpenChange={setPrepareOpen}
                timezone={displayTimezone}
            />

            {readyRestore ? (
                <ConfirmRestoreDialog
                    key={readyRestore.id}
                    backup={backup}
                    restoreData={readyRestore}
                    open={confirmOpen}
                    onOpenChange={setConfirmOpen}
                    timezone={displayTimezone}
                />
            ) : null}

            <ConfirmDialog
                open={deleting}
                onOpenChange={setDeleting}
                title="Hapus backup"
                destructive
                confirmLabel="Hapus"
                description="Backup akan dihapus dari penyimpanan dan riwayat. Tindakan ini tidak dapat dibatalkan."
                onConfirm={confirmDelete}
            />
        </>
    );
}

Show.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Backup & Pemulihan', href: index() },
    ],
};
