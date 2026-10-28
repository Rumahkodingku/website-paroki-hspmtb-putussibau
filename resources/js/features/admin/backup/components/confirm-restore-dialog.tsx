import { useForm } from '@inertiajs/react';
import type { ChangeEvent } from 'react';
import { AlertTriangle, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import { Field } from '@/components/form-fields';
import { Text } from '@/components/text';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { formatWibDateTime } from '@/lib/datetime';
import { restore } from '@/routes/backups';
import type { BackupDetail, BackupRestoreRow, RestoreFormData } from '../types';

export function ConfirmRestoreDialog({
    backup,
    restoreData,
    open,
    onOpenChange,
    timezone,
}: {
    backup: BackupDetail;
    restoreData: BackupRestoreRow;
    open: boolean;
    onOpenChange: (open: boolean) => void;
    timezone: string;
}) {
    const [acknowledged, setAcknowledged] = useState(false);
    const form = useForm<RestoreFormData>({
        restore_id: restoreData.id,
        confirmation: '',
    });

    const submit = () => {
        form.post(restore.url(backup.id), {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        });
    };

    const disabled =
        form.processing ||
        !restoreData.can_confirm ||
        !acknowledged ||
        form.data.confirmation !== 'PULIHKAN DATA';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Konfirmasi pemulihan</DialogTitle>
                    <DialogDescription>
                        Tindakan ini mengganti data aplikasi dengan isi backup.
                        Tidak dapat dibatalkan dari halaman ini.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <Card className="gap-0 py-0">
                        <CardContent className="space-y-2 p-4">
                            <Text as="p" variant="label">
                                {backup.type_label}
                            </Text>
                            <div className="flex items-center gap-2">
                                <Text as="span" variant="body-sm" color="muted">
                                    Safety backup:
                                </Text>
                                <Badge variant="outline">
                                    {restoreData.safety_backup_status ??
                                        'belum tersedia'}
                                </Badge>
                            </div>
                            {restoreData.expires_at ? (
                                <Text as="p" variant="body-sm" color="muted">
                                    Konfirmasi berlaku sampai{' '}
                                    {formatWibDateTime(
                                        restoreData.expires_at,
                                        timezone,
                                    )}
                                    .
                                </Text>
                            ) : null}
                        </CardContent>
                    </Card>

                    <Alert variant="destructive">
                        <AlertTriangle aria-hidden="true" />
                        <AlertTitle>Peringatan</AlertTitle>
                        <AlertDescription>
                            Data setelah waktu backup dapat hilang. Backup
                            database tidak memulihkan file upload. Full restore
                            mengganti file yang ada di arsip dan mempertahankan
                            file lain. Layanan dapat terganggu sementara.
                        </AlertDescription>
                    </Alert>

                    <label className="flex items-start gap-3 text-sm">
                        <Checkbox
                            checked={acknowledged}
                            onCheckedChange={(value) =>
                                setAcknowledged(value === true)
                            }
                        />
                        <span>
                            Saya memahami bahwa pemulihan akan mengubah data
                            aplikasi.
                        </span>
                    </label>

                    <Field
                        id="restore-confirmation"
                        label="Frasa konfirmasi"
                        hint="Ketik PULIHKAN DATA untuk mengonfirmasi."
                        error={form.errors.confirmation}
                        required
                    >
                        <Input
                            id="restore-confirmation"
                            value={form.data.confirmation}
                            onChange={(event: ChangeEvent<HTMLInputElement>) =>
                                form.setData('confirmation', event.target.value)
                            }
                            autoComplete="off"
                            aria-label="Frasa konfirmasi pemulihan"
                        />
                    </Field>

                    <Alert>
                        <ShieldCheck aria-hidden="true" />
                        <AlertTitle>Operasi terkunci</AlertTitle>
                        <AlertDescription>
                            Restore berjalan di antrean dan akan menolak operasi
                            lain yang berjalan bersamaan.
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
                    <Button type="button" onClick={submit} disabled={disabled}>
                        Konfirmasi Pemulihan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
