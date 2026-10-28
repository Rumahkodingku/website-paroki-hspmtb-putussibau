import { useForm } from '@inertiajs/react';
import { formatBytes } from '@uploadcn/core';
import { AlertTriangle, ShieldCheck } from 'lucide-react';
import { Text } from '@/components/text';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { formatWibDateTime } from '@/lib/datetime';
import { prepare } from '@/routes/backups/restore';
import type { BackupDetail } from '../types';

export function PrepareRestoreDialog({
    backup,
    open,
    onOpenChange,
    timezone,
}: {
    backup: BackupDetail;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    timezone: string;
}) {
    const form = useForm({});

    const submit = () => {
        form.post(prepare.url(backup.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Siapkan pemulihan</DialogTitle>
                    <DialogDescription>
                        Tahap ini belum mengubah data. Aplikasi akan membuat dan
                        memvalidasi safety backup terlebih dahulu.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <Card className="gap-0 py-0">
                        <CardContent className="space-y-1 p-4">
                            <Text as="p" variant="label">
                                {backup.type_label} · {backup.status_label}
                            </Text>
                            <Text as="p" variant="body-sm" color="muted">
                                {backup.created_at
                                    ? formatWibDateTime(
                                          backup.created_at,
                                          timezone,
                                      )
                                    : '-'}{' '}
                                ·{' '}
                                {backup.file_size === null
                                    ? 'ukuran tidak tersedia'
                                    : formatBytes(backup.file_size)}
                            </Text>
                        </CardContent>
                    </Card>

                    <Alert>
                        <AlertTriangle aria-hidden="true" />
                        <AlertTitle>Sebelum melanjutkan</AlertTitle>
                        <AlertDescription>
                            Data setelah waktu backup dapat hilang. Untuk backup
                            database, file upload tidak dipulihkan. Layanan
                            dapat terganggu sementara.
                        </AlertDescription>
                    </Alert>

                    <Alert>
                        <ShieldCheck aria-hidden="true" />
                        <AlertTitle>Safety backup wajib</AlertTitle>
                        <AlertDescription>
                            Safety backup wajib berhasil sebelum tombol
                            konfirmasi final tersedia.
                        </AlertDescription>
                    </Alert>
                </div>

                <DialogFooter>
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => onOpenChange(false)}
                        disabled={form.processing}
                    >
                        Batal
                    </Button>
                    <Button
                        type="button"
                        onClick={submit}
                        disabled={form.processing}
                    >
                        Siapkan safety backup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
