import { describe, expect, it } from 'vitest';
import { screen } from '@testing-library/react';
import { DownloadSummary } from '@/features/admin/dokumen/components/download-summary';
import { renderWithInertia } from '../../../support/inertia';

describe('DownloadSummary', () => {
    it('shows the total and links the most downloaded documents', () => {
        renderWithInertia(
            <DownloadSummary
                summary={{
                    total: 1234,
                    top: [
                        {
                            id: 7,
                            title: 'Pedoman Pelayanan',
                            download_count: 900,
                        },
                    ],
                }}
            />,
        );

        expect(screen.getByText('1.234')).toBeInTheDocument();
        expect(screen.getByText('Pedoman Pelayanan')).toBeInTheDocument();
        expect(
            screen.getByRole('link', { name: /Pedoman Pelayanan/ }),
        ).toHaveAttribute('href', '/admin/dokumen/7/ubah');
    });

    it('renders nothing when there are no downloads', () => {
        const { container } = renderWithInertia(
            <DownloadSummary summary={{ total: 0, top: [] }} />,
        );

        expect(container).toBeEmptyDOMElement();
    });
});
