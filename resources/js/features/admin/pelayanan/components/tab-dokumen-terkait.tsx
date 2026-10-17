import type { ChangeEvent } from 'react';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import type { ServiceDocumentOption, ServiceFormMeta } from '../types';
import type { ServiceFormInstance } from './service-form-instance';

type Props = {
    form: ServiceFormInstance;
    documents: ServiceDocumentOption[];
    copy: ServiceFormMeta['copy'];
    onSubmit: (event: ChangeEvent) => void;
};

export function TabDokumenTerkait({ form, documents, copy, onSubmit }: Props) {
    const selected = form.data.document_ids;

    const toggle = (id: number, checked: boolean) => {
        form.setData(
            'document_ids',
            checked
                ? [...selected, id]
                : selected.filter((value) => value !== id),
        );
    };

    return (
        <form noValidate onSubmit={onSubmit} className="space-y-6">
            <div className="space-y-1">
                <Text as="h3" variant="h3">
                    Dokumen Terkait
                </Text>
                <Text as="p" variant="caption" color="muted">
                    Pilih dokumen yang perlu dibaca atau diunduh umat sebelum
                    mendaftar layanan ini. Hanya dokumen berstatus Terbit yang
                    dapat ditautkan baru.
                </Text>
            </div>

            {form.errors.document_ids ? (
                <Text as="p" variant="caption" className="text-destructive">
                    {form.errors.document_ids}
                </Text>
            ) : null}

            {documents.length === 0 ? (
                <Text as="p" variant="body" color="muted">
                    Belum ada dokumen terbit yang dapat ditautkan.
                </Text>
            ) : (
                <ul className="space-y-3">
                    {documents.map((document) => {
                        const id = `document-${document.id}`;
                        const checked = selected.includes(document.id);

                        return (
                            <li
                                key={document.id}
                                className="flex items-start gap-3 rounded-md border border-hairline p-4"
                            >
                                <Checkbox
                                    id={id}
                                    checked={checked}
                                    onCheckedChange={(value) =>
                                        toggle(document.id, value === true)
                                    }
                                />
                                <div className="space-y-1">
                                    <Label htmlFor={id}>{document.title}</Label>
                                    {document.status !== 'published' ? (
                                        <Badge variant="outline">
                                            {document.status_label}
                                        </Badge>
                                    ) : null}
                                </div>
                            </li>
                        );
                    })}
                </ul>
            )}

            <Text as="p" variant="caption" color="muted">
                {copy.documents_hint ??
                    'Dokumen yang diarsipkan tidak lagi tampil di halaman publik, meskipun tautannya masih tersimpan.'}
            </Text>
        </form>
    );
}
