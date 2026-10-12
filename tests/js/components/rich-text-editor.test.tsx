import { describe, expect, it } from 'vite-plus/test';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { RichTextEditor } from '@/components/rich-text-editor';

/*
 * The toolbar's reactivity.
 *
 * The bug this covers: in Tiptap v3 useEditor no longer re-renders on every
 * transaction, so a toolbar that reads editor.isActive() during render computes
 * its state once and freezes. The formatting was applied, but the button never
 * lit up — which reads as "the editor did nothing".
 *
 * Clicking a mark with an empty selection sets ProseMirror's stored marks, so
 * isActive() flips without needing to type or select text first. That is the
 * smallest reproduction of the defect.
 */

describe('RichTextEditor', () => {
    it('lights the bold button up once the mark is active', async () => {
        render(<RichTextEditor value="<p>Halo</p>" onChange={() => {}} />);

        const bold = await screen.findByRole('button', { name: 'Tebal' });

        expect(bold).toHaveAttribute('aria-pressed', 'false');

        await userEvent.click(bold);

        await waitFor(() =>
            expect(bold).toHaveAttribute('aria-pressed', 'true'),
        );
    });
});
