import { Download, Eye, FileText } from 'lucide-react';
import { formatBytes } from '@uploadcn/core';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/text';
import { formatWibDate } from '@/lib/datetime';
import { unduh as downloadDocument } from '@/routes/dokumen';
import type { PublicDocumentCard as PublicDocumentCardData } from '@/types/document';

export function DocumentCard({
    document,
}: {
    document: PublicDocumentCardData;
}) {
    const downloadUrl = downloadDocument(document.id).url;
    const previewUrl = downloadDocument(document.id, {
        query: { inline: 1 },
    }).url;

    return (
        <article className="flex h-full flex-col rounded-lg border border-hairline bg-card p-6">
            <div className="flex items-start gap-4">
                <span
                    aria-hidden="true"
                    className="flex size-11 shrink-0 items-center justify-center rounded-md bg-canvas-soft text-primary"
                >
                    <FileText className="size-5" />
                </span>

                <div className="min-w-0 space-y-2">
                    <div className="flex flex-wrap items-center gap-2">
                        {document.category ? (
                            <Badge variant="secondary">
                                {document.category.name}
                            </Badge>
                        ) : null}
                        {document.is_featured ? <Badge>Unggulan</Badge> : null}
                        {document.is_expired ? (
                            <Badge variant="outline">Kedaluwarsa</Badge>
                        ) : null}
                    </div>

                    <Text as="h3" variant="h3" className="break-words">
                        {document.title}
                    </Text>
                </div>
            </div>

            {document.description ? (
                <Text
                    as="p"
                    variant="body"
                    color="muted"
                    className="mt-4 line-clamp-3"
                >
                    {document.description}
                </Text>
            ) : null}

            <dl className="mt-5 space-y-1">
                <div className="flex flex-wrap gap-x-2">
                    <dt className="sr-only">Format dan ukuran</dt>
                    <dd>
                        <Text as="p" variant="caption" color="muted">
                            {document.extension.toUpperCase()} ·{' '}
                            {formatBytes(document.file_size)}
                        </Text>
                    </dd>
                </div>

                {document.document_date ? (
                    <Text as="p" variant="caption" color="muted">
                        Tanggal dokumen: {formatWibDate(document.document_date)}
                    </Text>
                ) : null}

                {document.version_label ? (
                    <Text as="p" variant="caption" color="muted">
                        Versi: {document.version_label}
                    </Text>
                ) : null}
            </dl>

            <div className="mt-6 flex flex-wrap gap-3">
                <Button asChild>
                    <a
                        href={downloadUrl}
                        aria-label={`Unduh dokumen ${document.title}`}
                    >
                        <Download className="size-4" aria-hidden="true" />
                        Unduh Dokumen
                    </a>
                </Button>

                {document.is_pdf ? (
                    <Button variant="outline" asChild>
                        <a
                            href={previewUrl}
                            target="_blank"
                            rel="noreferrer"
                            aria-label={`Pratinjau dokumen ${document.title}`}
                        >
                            <Eye className="size-4" aria-hidden="true" />
                            Pratinjau PDF
                        </a>
                    </Button>
                ) : null}
            </div>
        </article>
    );
}
