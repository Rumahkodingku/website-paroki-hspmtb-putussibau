import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/react';
import { PublicServiceCard } from '@/features/public/pelayanan/components/service-card';
import type { PublicServiceCard as Card } from '@/features/public/pelayanan/types';

function card(overrides: Partial<Card> = {}): Card {
    return {
        id: 1,
        title: 'Sakramen Baptis',
        slug: 'baptis',
        thumbnail: null,
        thumbnail_alt: null,
        summary: 'Ringkasan singkat.',
        is_emergency: false,
        ...overrides,
    };
}

describe('PublicServiceCard', () => {
    it('renders title and summary', () => {
        render(<PublicServiceCard service={card()} />);
        expect(screen.getByText('Sakramen Baptis')).toBeInTheDocument();
        expect(screen.getByText('Ringkasan singkat.')).toBeInTheDocument();
    });

    it('shows emergency badge for emergency service', () => {
        render(<PublicServiceCard service={card({ is_emergency: true })} />);
        expect(screen.getByText('Pelayanan darurat')).toBeInTheDocument();
    });

    it('links to detail page by slug', () => {
        render(<PublicServiceCard service={card()} />);
        expect(screen.getByRole('link')).toHaveAttribute(
            'href',
            '/pelayanan/baptis',
        );
    });
});
