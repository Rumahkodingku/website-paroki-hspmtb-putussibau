import { describe, expect, it, vi } from 'vitest';
import { screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {
    DocumentForm,
    type DocumentFormInstance,
} from '@/features/admin/dokumen/components/document-form';
import type {
    DocumentFormData,
    DocumentFormMeta,
} from '@/features/admin/dokumen/types';
import { renderWithInertia } from '../../../support/inertia';

const META: DocumentFormMeta = {
    labels: {
        title: 'Judul dokumen',
        category: 'Kategori',
        description: 'Deskripsi',
        file: 'Berkas dokumen',
        version: 'Versi',
        document_date: 'Tanggal dokumen',
        expires_at: 'Berlaku sampai',
        status: 'Status publikasi',
        is_featured: 'Dokumen unggulan',
    },
    hints: {
        title: 'Nama dokumen.',
        category: 'Kategori untuk filter.',
        file: 'PDF maksimal 10 MB.',
        status: 'Draf tidak tampil publik.',
        is_featured: 'Tampil di bagian unggulan.',
    },
    copy: {
        publish: 'Terbitkan',
        save_draft: 'Simpan Draf',
        save_changes: 'Simpan Perubahan',
        featured: 'Unggulan',
        expired: 'Kedaluwarsa',
    },
    empty: {},
    statuses: [
        { value: 'draft', label: 'Draf' },
        { value: 'published', label: 'Terbit' },
        { value: 'archived', label: 'Arsip' },
    ],
};

const CATEGORIES = [{ id: 1, name: 'Pedoman' }];

function createData(
    overrides: Partial<DocumentFormData> = {},
): DocumentFormData {
    return {
        title: '',
        document_category_id: '1',
        description: '',
        version_label: '',
        document_date: '',
        expires_at: '',
        status: 'draft',
        is_featured: false,
        file: null,
        ...overrides,
    };
}

function renderForm(
    mode: 'create' | 'edit',
    initial: DocumentFormData,
    currentFileName: string | null = null,
) {
    const onSubmit = vi.fn<(form: DocumentFormInstance) => void>();

    renderWithInertia(
        <DocumentForm
            mode={mode}
            initial={initial}
            categories={CATEGORIES}
            meta={META}
            currentFileName={currentFileName}
            onSubmit={onSubmit}
            onCancel={() => {}}
        />,
    );

    return { onSubmit };
}

describe('DocumentForm', () => {
    it('shows the required fields and the document limits', () => {
        renderForm('create', createData());

        expect(screen.getByLabelText(/Judul dokumen/)).toBeInTheDocument();
        expect(screen.getByLabelText(/Kategori/)).toBeInTheDocument();
        expect(screen.getByLabelText(/Berkas dokumen/)).toBeInTheDocument();
        expect(screen.getByText(/PDF maksimal 10 MB/)).toBeInTheDocument();
        expect(
            screen.getByRole('button', { name: 'Simpan Draf' }),
        ).toBeInTheDocument();
    });

    it('shows the saved file on edit and does not require a replacement', () => {
        renderForm(
            'edit',
            createData({ title: 'Pedoman', status: 'published' }),
            'pedoman-lama.pdf',
        );

        expect(screen.getByText(/pedoman-lama\.pdf/)).toBeInTheDocument();
        expect(screen.getByLabelText(/Berkas dokumen/)).not.toBeRequired();
        expect(
            screen.getByRole('button', { name: 'Simpan Perubahan' }),
        ).toBeInTheDocument();
    });

    it('changes the submit label when the status changes to published', async () => {
        renderForm('create', createData());

        await userEvent.click(screen.getByLabelText(/Status publikasi/));
        await userEvent.click(screen.getByRole('option', { name: 'Terbit' }));

        expect(
            screen.getByRole('button', { name: 'Terbitkan' }),
        ).toBeInTheDocument();
    });
});
