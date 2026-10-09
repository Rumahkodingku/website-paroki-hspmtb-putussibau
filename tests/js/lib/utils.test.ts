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

    /**
     * The regression that made this registration necessary.
     *
     * tailwind-merge only knows Tailwind's own scales, and `text-` prefixes
     * both font-size and colour, so it classified every design typography token
     * as an unknown COLOUR. Two `text-*` classes therefore looked like a
     * conflict and the loser was discarded - silently, in the order given:
     *
     *     cn('text-body', 'text-foreground')  =>  'text-foreground'
     *
     * That was not theoretical. Every Button size variant lost its token this
     * way (so the whole application was rendering buttons at the browser's
     * default 16px), and Badge lost text-micro-legal.
     */
    it('keeps a design typography token alongside a text colour', () => {
        expect(cn('text-body', 'text-foreground')).toBe(
            'text-body text-foreground',
        );
        expect(cn('text-caption-strong', 'text-muted-foreground')).toBe(
            'text-caption-strong text-muted-foreground',
        );
    });

    /**
     * Once registered they are font sizes, so they conflict with each other
     * and with Tailwind's own sizes. Last one wins, which is what lets a caller
     * override a component's typography through className.
     */
    it('resolves two design typography tokens in favour of the last one', () => {
        expect(cn('text-body', 'text-caption')).toBe('text-caption');
        expect(cn('text-display-md', 'text-hero-display')).toBe(
            'text-hero-display',
        );
    });

    it('resolves a design token against a Tailwind size in favour of the last one', () => {
        expect(cn('text-body', 'text-2xl')).toBe('text-2xl');
        expect(cn('text-2xl', 'text-body')).toBe('text-body');
        expect(cn('text-sm', 'text-body')).toBe('text-body');
    });

    /**
     * Registering the sizes must not disturb Tailwind's own text-colour
     * handling, which is why the tokens are listed under font-size and the
     * colours are left to fall through to the group that already existed.
     */
    it('still resolves two text colours in favour of the last one', () => {
        expect(cn('text-muted-foreground', 'text-foreground')).toBe(
            'text-foreground',
        );
        expect(cn('text-body', 'text-navy', 'dark:text-navy-light')).toBe(
            'text-body text-navy dark:text-navy-light',
        );
    });

    /**
     * A responsive step-down is two different breakpoints, not two sizes, so
     * both have to survive. This is the pattern a Text caller writes when
     * applying the roadmap's 56 -> 40 -> 34 -> 28 hero strategy.
     */
    it('keeps typography tokens at different breakpoints', () => {
        expect(cn('text-hero-display', 'md:text-display-lg')).toBe(
            'text-hero-display md:text-display-lg',
        );
    });

    /**
     * The Button and Badge cases, named because they are the two that were
     * silently rendering at the wrong size. Without this the registration in
     * lib/utils.ts could be reverted as "unnecessary" and nothing else here
     * would notice.
     */
    it('keeps a Button size token through its variant merge', () => {
        const base = 'inline-flex items-center font-medium whitespace-nowrap';

        expect(
            cn(
                base,
                'h-11 px-[22px] text-body',
                'bg-primary text-primary-foreground',
            ),
        ).toContain('text-body');

        expect(
            cn(
                base,
                'h-9 px-4 text-button-utility',
                'bg-primary text-primary-foreground',
            ),
        ).toContain('text-button-utility');

        expect(
            cn(
                base,
                'h-12 px-8 text-button-large',
                'bg-primary text-primary-foreground',
            ),
        ).toContain('text-button-large');
    });

    it('keeps the Badge size token through its variant merge', () => {
        expect(
            cn(
                'inline-flex items-center font-medium',
                'text-micro-legal',
                'border-transparent bg-primary text-primary-foreground',
            ),
        ).toContain('text-micro-legal');
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
