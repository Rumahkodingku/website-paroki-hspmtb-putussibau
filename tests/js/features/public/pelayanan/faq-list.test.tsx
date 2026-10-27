import { describe, expect, it } from 'vitest';
import { fireEvent, render, screen, waitFor } from '@testing-library/react';
import { FaqList } from '@/features/public/pelayanan/components/faq-list';
import type { PublicServiceFaq } from '@/features/public/pelayanan/types';

function faq(overrides: Partial<PublicServiceFaq> = {}): PublicServiceFaq {
    return {
        id: 1,
        question: 'Apakah ada biaya?',
        answer: 'Persembahan bersifat sukarela.',
        ...overrides,
    };
}

describe('FaqList', () => {
    it('renders nothing when empty', () => {
        const { container } = render(<FaqList items={[]} />);
        expect(container).toBeEmptyDOMElement();
    });

    it('renders questions and answers', async () => {
        render(<FaqList items={[faq()]} />);
        expect(screen.getByText('Apakah ada biaya?')).toBeInTheDocument();
        fireEvent.click(screen.getByRole('button'));
        await waitFor(() => {
            expect(
                screen.getByText('Persembahan bersifat sukarela.'),
            ).toBeInTheDocument();
        });
    });
});
