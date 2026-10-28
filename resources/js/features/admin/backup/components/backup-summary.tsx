import { formatBytes } from '@uploadcn/core';
import {
    AlertTriangle,
    CalendarClock,
    DatabaseBackup,
    HardDrive,
} from 'lucide-react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { formatWibDateTime } from '@/lib/datetime';
import type { BackupSummary as Summary, Readiness } from '../types';

function ReadinessRow({
    label,
    ready,
    detail,
}: {
    label: string;
    ready: boolean;
    detail?: string;
}) {
    return (
        <div className="flex items-start justify-between gap-3">
            <Text as="span" variant="body-sm">
                {label}
            </Text>
            <div className="flex flex-col items-end gap-1 text-right">
                <Badge variant={ready ? 'default' : 'outline'}>
                    {ready ? 'Siap' : 'Belum terdeteksi'}
                </Badge>
                {detail ? (
                    <Text as="p" variant="body-sm" color="muted">
                        {detail}
                    </Text>
                ) : null}
            </div>
        </div>
    );
}

export function BackupSummary({
    summary,
    readiness,
    timezone,
}: {
    summary?: Summary;
    readiness: Readiness;
    timezone: string;
}) {
    return (
        <div className="grid gap-4 lg:grid-cols-3">
            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <DatabaseBackup className="size-4" aria-hidden="true" />
                        Backup terakhir
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-2">
                    {summary?.last_success ? (
                        <>
                            <Text as="p" variant="label">
                                {summary.last_success.type_label} ·{' '}
                                {summary.last_success.status_label}
                            </Text>
                            <Text as="p" variant="body-sm" color="muted">
                                {summary.last_success.created_at
                                    ? formatWibDateTime(
                                          summary.last_success.created_at,
                                          timezone,
                                      )
                                    : '-'}
                            </Text>
                            <Text as="p" variant="body-sm" color="muted">
                                {summary.last_success.file_size === null
                                    ? 'Ukuran belum tersedia'
                                    : formatBytes(
                                          summary.last_success.file_size,
                                      )}
                            </Text>
                        </>
                    ) : (
                        <CardDescription>
                            Belum ada backup yang berhasil.
                        </CardDescription>
                    )}
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <CalendarClock className="size-4" aria-hidden="true" />
                        Kesiapan otomatis
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <ReadinessRow
                        label="Scheduler"
                        ready={readiness.scheduler.ready}
                        detail={
                            readiness.scheduler.last_seen_at
                                ? formatWibDateTime(
                                      readiness.scheduler.last_seen_at,
                                      timezone,
                                  )
                                : undefined
                        }
                    />
                    <Separator />
                    <ReadinessRow
                        label="Worker antrean"
                        ready={readiness.worker.ready}
                        detail={
                            readiness.worker.last_seen_at
                                ? formatWibDateTime(
                                      readiness.worker.last_seen_at,
                                      timezone,
                                  )
                                : undefined
                        }
                    />
                    <Separator />
                    <ReadinessRow
                        label="Perangkat database"
                        ready={readiness.binary.ready}
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle className="flex items-center gap-2">
                        <HardDrive className="size-4" aria-hidden="true" />
                        Penyimpanan
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <ReadinessRow
                        label="Lokasi"
                        ready
                        detail={
                            readiness.storage.local
                                ? 'Disk lokal privat'
                                : 'Object storage'
                        }
                    />
                    <Separator />
                    <ReadinessRow
                        label="Ruang tersedia"
                        ready={!readiness.storage.low}
                        detail={
                            readiness.storage.free_bytes === null
                                ? 'Tidak dapat diukur'
                                : formatBytes(readiness.storage.free_bytes)
                        }
                    />
                    {readiness.storage.local ? (
                        <Text
                            as="p"
                            variant="body-sm"
                            color="muted"
                            className="flex gap-2"
                        >
                            <AlertTriangle
                                className="mt-0.5 size-4 shrink-0"
                                aria-hidden="true"
                            />
                            Backup berada di server yang sama. Kehilangan server
                            juga menghilangkan backup.
                        </Text>
                    ) : null}
                </CardContent>
            </Card>
        </div>
    );
}
