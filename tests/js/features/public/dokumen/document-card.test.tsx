import { describe, expect, it } from 'vite-plus/test';
import { render, screen } from '@testing-library/react';
import { DocumentCard } from '@/components/document-card';
import type { PublicDocumentCard } from '@/types/document';

function documentData(
    overrides: Partial<PublicDocumentCard> = {},
): PublicDocumentCard {
    return {
        id: 7,
        title: 'Pedoman Pelayanan',
        description: 'Panduan singkat pelayanan paroki.',
        category: { id: 1, name: 'Pedoman', slug: 'pedoman' },
        extension: 'pdf',
        file_size: 2048,
        version_label: '1.0',
        document_date: '2026-10-01',
        is_featured: false,
        is_expired: false,
        is_pdf: true,
        published_at: '2026-10-01T00:00:00+00:00',
        updated_at: '2026-10-01T00:00:00+00:00',
        ...overrides,
    };
}

describe('DocumentCard', () => {
    it('shows the format, size, date, and version as text', () => {
        render(<DocumentCard document={documentData()} />);

        expect(screen.getByText(/2 KB/)).toBeInTheDocument();
        expect(screen.getByText(/2026/)).toBeInTheDocument();
        expect(screen.getByText(/Versi: 1.0/)).toBeInTheDocument();
    });

    it('labels the download link with the document title', () => {
        render(<DocumentCard document={documentData()} />);

        expect(
            screen.getByRole('link', {
                name: 'Unduh dokumen Pedoman Pelayanan',
            }),
        ).toHaveAttribute('href', '/dokumen/7/unduh');
    });

    it('offers a PDF preview only for PDFs', () => {
        const { rerender } = render(<DocumentCard document={documentData()} />);

        expect(
            screen.getByRole('link', {
                name: 'Pratinjau dokumen Pedoman Pelayanan',
            }),
        ).toBeInTheDocument();

        rerender(
            <DocumentCard
                document={documentData({
                    extension: 'docx',
                    is_pdf: false,
                })}
            />,
        );

        expect(
            screen.queryByRole('link', {
                name: 'Pratinjau dokumen Pedoman Pelayanan',
            }),
        ).toBeNull();
    });

    it('marks featured and expired documents', () => {
        render(
            <DocumentCard
                document={documentData({
                    is_featured: true,
                    is_expired: true,
                })}
            />,
        );

        expect(screen.getByText('Unggulan')).toBeInTheDocument();
        expect(screen.getByText('Kedaluwarsa')).toBeInTheDocument();
    });
});
