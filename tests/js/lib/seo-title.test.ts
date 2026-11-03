import { describe, expect, it } from 'vitest';
import { formatSeoTitle } from '@/lib/seo-title';

describe('formatSeoTitle', () => {
    it('appends the parish name with a pipe', () => {
        expect(formatSeoTitle('Berita & Artikel', 'HSPMTB Putussibau')).toBe(
            'Berita & Artikel | HSPMTB Putussibau',
        );
    });

    it('does not duplicate the name when the title already is the name', () => {
        expect(formatSeoTitle('HSPMTB Putussibau', 'HSPMTB Putussibau')).toBe(
            'HSPMTB Putussibau',
        );
    });

    it('does not repeat a site name already present in the title', () => {
        expect(
            formatSeoTitle('Paroki HSPMTB Putussibau', 'HSPMTB Putussibau'),
        ).toBe('Paroki HSPMTB Putussibau');
    });

    it('returns the title when no site name is configured', () => {
        expect(formatSeoTitle('Berita', null)).toBe('Berita');
        expect(formatSeoTitle('Berita', '  ')).toBe('Berita');
    });
});
