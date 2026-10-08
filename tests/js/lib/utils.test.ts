import { describe, expect, it } from 'vite-plus/test';
import { cn, toUrl } from '@/lib/utils';

describe('cn', () => {
    it('joins class names the way clsx does', () => {
        expect(cn('rounded-md', 'border')).toBe('rounded-md border');
    });

    /**
     * This is the falsy value a caller actually hands to cn(), because they
     * wrote `isActive && 'active'` upstream: the && has already produced a plain
     * false by the time cn() sees it. Letting it through would ship a literal
     * "false" class into the DOM from every such call site.
     */
    it('drops falsy values instead of emitting "false"', () => {
        expect(cn('base', false, null, undefined)).toBe('base');
    });

    /**
     * The point of twMerge in this codebase: a caller passes a layout class and
     * a caller-supplied className, and without merging both would end up on the
     * element with the winner decided by stylesheet order rather than by the
     * caller. So the second padding utility has to win.
     */
    it('resolves conflicting Tailwind utilities in favour of the last one', () => {
        expect(cn('p-2', 'p-4')).toBe('p-4');
        expect(cn('pr-10', 'pr-2')).toBe('pr-2');
    });

    it('keeps utilities that do not actually conflict', () => {
        expect(cn('text-sm', 'font-bold')).toBe('text-sm font-bold');
    });
});

describe('toUrl', () => {
    it('passes a string href through unchanged', () => {
        expect(toUrl('/admin/pengaturan')).toBe('/admin/pengaturan');
    });

    /**
     * Inertia types an href as a string or as a descriptor object, so a helper
     * that only handled strings would type-check and then render "[object
     * Object]" at runtime.
     */
    it('unwraps the object form of a Wayfinder link descriptor', () => {
        expect(toUrl({ url: '/admin/media' } as never)).toBe('/admin/media');
    });
});
