import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, ShieldCheck } from 'lucide-react';
import Heading from '@/components/heading';
import { Text } from '@/components/text';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { dashboard } from '@/routes';
import { index } from '@/routes/backups';
import type { SharedProps } from '@/types';
import { BackupSettingsForm } from '../components/backup-settings-form';
import type { BackupSettingsProps } from '../types';

function ReadinessItem({ label, ready }: { label: string; ready: boolean }) {
    return (
        <div className="flex items-center justify-between gap-4">
            <Text as="span" variant="body-sm">
                {label}
            </Text>
            <Badge variant={ready ? 'default' : 'outline'}>
                {ready ? 'Siap' : 'Belum terdeteksi'}
            </Badge>
        </div>
    );
}

export default function Pengaturan({ settingsPage }: BackupSettingsProps) {
    const { displayTimezone } = usePage<SharedProps>().props;
    const { settings, timezones, readiness } = settingsPage;

    return (
        <>
            <Head title="Pengaturan Backup" />

            <div className="space-y-6 p-6">
                <div className="space-y-2">
                    <Button asChild variant="ghost" size="sm" className="-ml-3">
                        <Link href={index.url()}>
                            <ArrowLeft className="size-4" aria-hidden="true" />
                            Kembali ke Backup
                        </Link>
                    </Button>

                    <Heading
                        variant="small"
                        title="Pengaturan Backup"
                        description={`Atur jadwal, retensi, dan batas operasi backup. Waktu ditampilkan dalam ${displayTimezone}.`}
                    />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Kesiapan operasional</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        <ReadinessItem
                            label="Scheduler"
                            ready={readiness.scheduler.ready}
                        />
                        <Separator />
                        <ReadinessItem
                            label="Worker antrean"
                            ready={readiness.worker.ready}
                        />
                        <Separator />
                        <ReadinessItem
                            label="Perangkat database"
                            ready={readiness.binary.ready}
                        />
                        <Separator />
                        <ReadinessItem
                            label="Ruang disk"
                            ready={!readiness.storage.low}
                        />
                    </CardContent>
                </Card>

                {!readiness.scheduler.ready || !readiness.worker.ready ? (
                    <Alert>
                        <AlertTriangle aria-hidden="true" />
                        <AlertTitle>Otomatisasi belum siap</AlertTitle>
                        <AlertDescription>
                            Jadwal tersimpan, tetapi scheduler atau worker belum
                            terdeteksi. Backup otomatis tidak akan berjalan
                            sampai keduanya aktif di server.
                        </AlertDescription>
                    </Alert>
                ) : null}

                {readiness.storage.local ? (
                    <Alert>
                        <ShieldCheck aria-hidden="true" />
                        <AlertTitle>Penyimpanan lokal</AlertTitle>
                        <AlertDescription>
                            Backup disimpan di disk privat pada server yang
                            sama. Kehilangan server juga menghilangkan backup.
                        </AlertDescription>
                    </Alert>
                ) : null}

                <BackupSettingsForm settings={settings} timezones={timezones} />
            </div>
        </>
    );
}

Pengaturan.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Backup & Pemulihan', href: index() },
        { title: 'Pengaturan' },
    ],
};
