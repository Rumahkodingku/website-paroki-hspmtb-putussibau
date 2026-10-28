import { useForm } from '@inertiajs/react';
import { formatBytes } from '@uploadcn/core';
import { MediaUploadField } from '@/components/media-upload-field';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { upload } from '@/routes/backups';
import type { Readiness, UploadBackupFormData } from '../types';

export function UploadBackupDialog({
    open,
    onOpenChange,
    readiness,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    readiness: Readiness;
}) {
    const form = useForm<UploadBackupFormData>({ file: null });

    const submit = () => {
        form.post(upload.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                onOpenChange(false);
            },
        });
    };

    const limit =
        readiness.upload_limit.effective > 0
            ? formatBytes(readiness.upload_limit.effective)
            : 'sesuai server';

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Unggah backup eksternal</DialogTitle>
                    <DialogDescription>
                        Hanya arsip backup yang dibuat aplikasi ini yang
                        diterima. Unggahan tidak langsung memulihkan data.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-4">
                    <MediaUploadField
                        variant="backup"
                        id="backup-file"
                        label="Arsip backup"
                        hint={`Format .sql.gz atau .zip. Maksimal ${limit}.`}
                        accept={[
                            '.sql.gz',
                            '.zip',
                            'application/gzip',
                            'application/zip',
                        ]}
                        maxSize={readiness.upload_limit.effective}
                        error={form.errors.file}
                        required
                        onFileChange={(file) => form.setData('file', file)}
                    />

                    {form.progress ? (
                        <div className="space-y-1">
                            <div className="h-2 overflow-hidden rounded-full bg-muted">
                                <div
                                    className="h-full bg-primary transition-all"
                                    style={{
                                        width: `${form.progress.percentage ?? 0}%`,
                                    }}
                                />
                            </div>
                            <Text as="p" variant="body-sm" color="muted">
                                {form.progress.percentage ?? 0}% terunggah
                            </Text>
                        </div>
                    ) : null}
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
                        disabled={form.processing || form.data.file === null}
                    >
                        Unggah dan validasi
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
