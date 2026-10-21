import { describe, expect, it } from 'vitest';
import { screen } from '@testing-library/react';
import Index from '@/features/admin/master-data/pages/kategori-dokumen/index';
import type { Paginated } from '@/types';
import { renderWithInertia } from '../../../../support/inertia';

const EMPTY: Paginated<never> = {
    data: [],
    links: { first: null, last: null, prev: null, next: null },
    meta: {
        current_page: 1,
        from: null,
        last_page: 1,
        per_page: 15,
        to: null,
        total: 0,
        links: [],
    },
};

const COPY = {
    title: 'Kategori Dokumen',
    description: 'Kelompokkan dokumen.',
};

describe('Kategori Dokumen index', () => {
    it('shows the server refusal when a category is still in use', () => {
        renderWithInertia(
            <Index
                categories={EMPTY}
                filters={{
                    search: '',
                    sort: '',
                    direction: '',
                }}
                copy={COPY}
            />,
            {
                errors: {
                    document_category:
                        'Kategori tidak dapat dihapus karena masih memiliki 2 dokumen.',
                },
            },
        );

        expect(
            screen.getByText(
                'Kategori tidak dapat dihapus karena masih memiliki 2 dokumen.',
            ),
        ).toBeInTheDocument();
    });
});
