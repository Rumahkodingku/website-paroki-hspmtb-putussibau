import { useForm } from '@inertiajs/react';
import { Database, Package } from 'lucide-react';
import { ChoiceSelect, Field } from '@/components/form-fields';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { store } from '@/routes/backups';
import type { BackupFormData } from '../types';

export function CreateBackupDialog({
    open,
    onOpenChange,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const form = useForm<BackupFormData>({
        type: 'database',
        note: '',
    });

    const submit = () => {
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Buat backup</DialogTitle>
                    <DialogDescription>
                        Backup dibuat di latar belakang. Anda dapat memantau
                        statusnya di halaman ini.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-5">
                    <Field
                        id="backup-type"
                        label="Jenis backup"
                        error={form.errors.type}
                        isRequired
                    >
                        <ChoiceSelect
                            id="backup-type"
                            value={form.data.type}
                            onChange={(value) =>
                                form.setData(
                                    'type',
                                    value === 'full' ? 'full' : 'database',
                                )
                            }
                            options={[
                                {
                                    value: 'database',
                                    label: 'Backup Database',
                                },
                                {
                                    value: 'full',
                                    label: 'Backup Lengkap',
                                },
                            ]}
                        />
                    </Field>

                    <Alert>
                        {form.data.type === 'database' ? (
                            <Database aria-hidden="true" />
                        ) : (
                            <Package aria-hidden="true" />
                        )}
                        <AlertTitle>
                            {form.data.type === 'database'
                                ? 'Database saja'
                                : 'Database dan file upload'}
                        </AlertTitle>
                        <AlertDescription>
                            {form.data.type === 'database'
                                ? 'Memuat struktur dan data database. File media dan dokumen tidak disertakan.'
                                : 'Memuat database, media asli, varian gambar, dan dokumen. Proses lebih lama dan menggunakan lebih banyak ruang.'}
                        </AlertDescription>
                    </Alert>

                    <Field
                        id="backup-note"
                        label="Catatan (opsional)"
                        hint="Jangan masukkan kata sandi atau data sensitif."
                        error={form.errors.note}
                    >
                        <Textarea
                            id="backup-note"
                            value={form.data.note}
                            onChange={(event) =>
                                form.setData('note', event.target.value)
                            }
                            rows={3}
                            maxLength={500}
                        />
                    </Field>
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
                        Buat backup
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
