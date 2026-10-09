import { describe, expect, it, vi } from 'vite-plus/test';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { Text } from '@/components/text';

/**
 * Text - the typography foundation.
 *
 * These tests assert on rendered output: the element that appears, the
 * classes on it, and the attributes that reach the DOM. They deliberately do
 * not import the variant map, because a test that reads its expectation from
 * the component under test proves only that the component agrees with itself.
 *
 * Where a class IS asserted it is asserted as a design-system token
 * (`text-caption`, `text-muted-foreground`), never as a Tailwind scale step,
 * for the reason public-primitives.test.tsx gives: DESIGN.md owns the visual
 * values and is allowed to change them without breaking a caller.
 */

describe('Text', () => {
    it('renders its children', () => {
        render(<Text>Paroki HSPMTB Putussibau</Text>);

        expect(
            screen.getByText('Paroki HSPMTB Putussibau'),
        ).toBeInTheDocument();
    });

    it('renders a span with body typography by default', () => {
        render(<Text>Tanpa variant</Text>);

        const element = screen.getByText('Tanpa variant');

        expect(element.tagName).toBe('SPAN');
        expect(element).toHaveClass('text-body');
    });

    /*
     * The token has to survive the merge. cn() once classified an
     * unregistered text-* class as a colour, which meant `text-body` met
     * `text-foreground` and lost; D-33 records that.
     */
    it('keeps both the typography token and the colour', () => {
        render(
            <Text variant="caption" color="muted">
                Keduanya
            </Text>,
        );

        const element = screen.getByText('Keduanya');

        expect(element).toHaveClass('text-caption');
        expect(element).toHaveClass('text-muted-foreground');
    });

    it.each([
        ['hero', 'text-hero-display'],
        ['display-lg', 'text-display-lg'],
        ['display-md', 'text-display-md'],
        ['lead', 'text-lead'],
        ['lead-airy', 'text-lead-airy'],
        ['tagline', 'text-tagline'],
        ['body-strong', 'text-body-strong'],
        ['caption-strong', 'text-caption-strong'],
        ['nav', 'text-nav-link'],
        ['fine-print', 'text-fine-print'],
        ['micro-legal', 'text-micro-legal'],
    ] as const)('maps variant "%s" to its design token', (variant, token) => {
        render(<Text variant={variant}>Contoh</Text>);

        expect(screen.getByText('Contoh')).toHaveClass(token);
    });

    /*
     * DESIGN.md defines three display steps and never defines heading levels,
     * so h1..h6 are mapped onto steps that already existed. Before this
     * mapping h1, h2 and h3 all rendered at display-md, which meant a document
     * outline carried no visual hierarchy at all.
     */
    it.each([
        ['h1', 1, 'text-display-lg'],
        ['h2', 2, 'text-display-md'],
        ['h3', 3, 'text-tagline'],
        ['h4', 4, 'text-body-strong'],
        ['h5', 5, 'text-caption-strong'],
        ['h6', 6, 'text-caption-strong'],
    ] as const)(
        'renders variant "%s" as a level-%s heading',
        (variant, level, token) => {
            render(<Text variant={variant}>Judul</Text>);

            const heading = screen.getByRole('heading', {
                level,
                name: 'Judul',
            });

            expect(heading).toHaveClass(token);
        },
    );

    /**
     * A span styled like a heading is a heading no screen reader can find by
     * stepping through levels, which is the mistake a polymorphic API is most
     * able to cause. The default element is derived from the variant so it
     * cannot happen by omission.
     */
    it('defaults a heading variant to its own tag rather than a span', () => {
        render(<Text variant="h3">Tingkat tiga</Text>);

        expect(
            screen.getByRole('heading', { level: 3, name: 'Tingkat tiga' })
                .tagName,
        ).toBe('H3');
    });

    /**
     * Every display heading in the application was carrying text-balance as a
     * hand-written className - four separate copies - because that is where
     * the browser's default line breaking produces an orphan that reads as a
     * layout bug. It belongs to the variant, and a caller who disagrees can
     * pass text-pretty, which tailwind-merge resolves as the later value.
     */
    it.each([
        ['hero', true],
        ['display-lg', true],
        ['display-md', true],
        ['h1', true],
        ['h2', true],
        ['h3', false],
        ['h4', false],
        ['body-strong', false],
    ] as const)('carries text-balance on %s: %s', (variant, balanced) => {
        render(<Text variant={variant}>Contoh</Text>);

        const element = screen.getByText('Contoh');

        if (balanced) {
            expect(element).toHaveClass('text-balance');
        } else {
            expect(element.className).not.toMatch(/text-balance/);
        }
    });

    it('lets a caller override text-balance with text-pretty', () => {
        render(
            <Text variant="h1" className="text-pretty">
                Rata kanan
            </Text>,
        );

        const element = screen.getByText('Rata kanan');

        expect(element).toHaveClass('text-pretty');
        expect(element.className).not.toMatch(/text-balance/);
    });

    it('renders a blockquote variant as a blockquote', () => {
        render(<Text variant="blockquote">Kutipan</Text>);

        expect(screen.getByText('Kutipan').tagName).toBe('BLOCKQUOTE');
    });

    /**
     * The rich-text blockquote rule in app.css and this variant have to agree:
     * the administrator previews the editor at one size and the visitor sees
     * another otherwise.
     */
    it('gives the blockquote variant the documented rich-text treatment', () => {
        render(<Text variant="blockquote">Kutipan</Text>);

        const quote = screen.getByText('Kutipan');

        expect(quote).toHaveClass('border-s-4', 'border-primary', 'ps-4');
        expect(quote).toHaveClass('text-navy', 'italic');
        // Not optional: text-navy on the dark canvas measures 1.25:1.
        expect(quote).toHaveClass('dark:text-navy-light');
    });

    /*
     * Style and element are separate. `as` picks the tag and must not change
     * the typography, which is the property that lets a heading variant be
     * rendered as something else without losing its step.
     */
    it('lets `as` choose the element without changing the variant', () => {
        render(
            <Text as="span" variant="display-md">
                Bukan heading
            </Text>,
        );

        const element = screen.getByText('Bukan heading');

        expect(element.tagName).toBe('SPAN');
        expect(element).toHaveClass('text-display-md');
    });

    it.each(['h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'p', 'span', 'div'] as const)(
        'renders as <%s> when asked',
        (tag) => {
            render(<Text as={tag}>Elemen</Text>);

            expect(screen.getByText('Elemen').tagName).toBe(tag.toUpperCase());
        },
    );

    it('can render as a label bound to a form control', () => {
        render(
            <>
                <Text as="label" variant="caption-strong" htmlFor="nama">
                    Nama
                </Text>
                <input id="nama" />
            </>,
        );

        expect(screen.getByLabelText('Nama')).toBeInTheDocument();
    });

    it('appends a caller className', () => {
        render(<Text className="mt-4 max-w-sm">Dengan kelas</Text>);

        expect(screen.getByText('Dengan kelas')).toHaveClass(
            'text-body',
            'mt-4',
            'max-w-sm',
        );
    });

    /*
     * twMerge decides conflicts by last-one-wins, so a caller can override the
     * component's own typography. This only holds because the token is
     * registered as a font-size; without that, `text-body` would beat the
     * caller's class and the override would be silently ignored.
     */
    it('lets a caller className override the variant typography', () => {
        render(<Text className="text-micro-legal">Ditimpa</Text>);

        expect(screen.getByText('Ditimpa')).toHaveClass('text-micro-legal');
        expect(screen.getByText('Ditimpa')).not.toHaveClass('text-body');
    });

    it('forwards style, and native attributes', () => {
        render(
            <Text
                style={{ letterSpacing: '0.1em' }}
                id="ringkasan"
                title="Ringkasan paroki"
                lang="id"
                dir="ltr"
            >
                Atribut
            </Text>,
        );

        const element = screen.getByText('Atribut');

        /*
         * Read off the style attribute rather than through toHaveStyle.
         * happy-dom keeps the declaration as written, which is what the point
         * is here: a value CSS cannot express has to survive the pass-through
         * untouched, and jest-dom's own computed-style comparison does not
         * report a custom property the way the DOM does.
         */
        expect(element.getAttribute('style')).toContain('letter-spacing');
        expect(element.getAttribute('style')).toContain('0.1em');
        expect(element).toHaveAttribute('id', 'ringkasan');
        expect(element).toHaveAttribute('title', 'Ringkasan paroki');
        expect(element).toHaveAttribute('lang', 'id');
        expect(element).toHaveAttribute('dir', 'ltr');
    });

    it('forwards aria-* and data-* attributes', () => {
        render(
            <Text
                aria-label="Nomor sekretariat"
                aria-describedby="catatan"
                data-test="kontak"
            >
                Kontak
            </Text>,
        );

        const element = screen.getByLabelText('Nomor sekretariat');

        expect(element).toHaveAttribute('aria-describedby', 'catatan');
        expect(element).toHaveAttribute('data-test', 'kontak');
    });

    it('keeps an event handler working', async () => {
        const user = userEvent.setup();
        const onClick = vi.fn();

        render(
            <Text as="button" onClick={onClick} type="button">
                Tekan
            </Text>,
        );

        await user.click(screen.getByRole('button', { name: 'Tekan' }));

        expect(onClick).toHaveBeenCalledOnce();
    });

    /*
     * There is no `success` and no `warning` colour, and that is deliberate:
     * DESIGN.md has no green or amber in its palette and D-22 already removed
     * the text-green-600 success messages for the same reason. These are the
     * colours that do exist.
     */
    it.each([
        ['foreground', 'text-foreground'],
        ['muted', 'text-muted-foreground'],
        ['subtle', 'text-ink-muted-soft'],
        ['brand', 'text-navy'],
        ['brand-inverse', 'text-navy-light'],
        ['danger', 'text-red-on-surface'],
        ['inherit', 'text-inherit'],
    ] as const)('maps colour "%s" to its token', (color, token) => {
        render(<Text color={color}>Warna</Text>);

        expect(screen.getByText('Warna')).toHaveClass(token);
    });

    /**
     * navy as text is invisible on the dark canvas - 1.25:1 - so the override
     * travels inside the variant. Four auth and account pages were writing the
     * pair by hand, and a caller who remembers it on one page and not the next
     * ships unreadable text in the middle of a flow.
     */
    it('carries the dark override inside the brand colour', () => {
        render(<Text color="brand">Merek</Text>);

        expect(screen.getByText('Merek')).toHaveClass(
            'text-navy',
            'dark:text-navy-light',
        );
    });

    /**
     * `default` emits no colour class at all rather than text-foreground, so a
     * Text inside a surface that sets its own colour - ParishCta's bg-navy
     * with text-white - inherits it instead of fighting it.
     */
    it('emits no colour class by default so the surface colour wins', () => {
        render(<Text variant="body-strong">Ikut induk</Text>);

        const element = screen.getByText('Ikut induk');

        expect(element).toHaveClass('text-body-strong');
        expect(element.className).not.toMatch(/text-foreground/);
    });

    it.each([
        ['left', 'text-left'],
        ['center', 'text-center'],
        ['right', 'text-right'],
        ['justify', 'text-justify'],
        ['start', 'text-start'],
        ['end', 'text-end'],
    ] as const)('applies align "%s"', (align, utility) => {
        render(<Text align={align}>Rata</Text>);

        expect(screen.getByText('Rata')).toHaveClass(utility);
    });

    it.each([
        ['uppercase', 'uppercase'],
        ['lowercase', 'lowercase'],
        ['capitalize', 'capitalize'],
        ['none', 'normal-case'],
    ] as const)('applies transform "%s"', (transform, utility) => {
        render(<Text transform={transform}>Transform</Text>);

        expect(screen.getByText('Transform')).toHaveClass(utility);
    });

    it.each([
        ['underline', 'underline'],
        ['overline', 'overline'],
        ['line-through', 'line-through'],
        ['none', 'no-underline'],
    ] as const)('applies decoration "%s"', (decoration, utility) => {
        render(<Text decoration={decoration}>Dekorasi</Text>);

        expect(screen.getByText('Dekorasi')).toHaveClass(utility);
    });

    it('applies italic', () => {
        render(<Text italic>Miring</Text>);

        expect(screen.getByText('Miring')).toHaveClass('italic');
    });

    /*
     * DESIGN.md: "Weight 600 is the main heading emphasis; avoid unnecessary
     * 700-heavy typography." There is no bold in this API.
     */
    it.each([
        ['light', 'font-light'],
        ['normal', 'font-normal'],
        ['medium', 'font-medium'],
        ['semibold', 'font-semibold'],
    ] as const)('applies weight "%s"', (weight, utility) => {
        render(<Text weight={weight}>Bobot</Text>);

        expect(screen.getByText('Bobot')).toHaveClass(utility);
    });

    it.each([
        ['tight', 'leading-tight'],
        ['snug', 'leading-snug'],
        ['relaxed', 'leading-relaxed'],
    ] as const)('applies leading "%s"', (leading, utility) => {
        render(<Text leading={leading}>Baris</Text>);

        expect(screen.getByText('Baris')).toHaveClass(utility);
    });

    it.each([
        ['tighter', 'tracking-tighter'],
        ['tight', 'tracking-tight'],
        ['wider', 'tracking-wider'],
        ['widest', 'tracking-widest'],
    ] as const)('applies tracking "%s"', (tracking, utility) => {
        render(<Text tracking={tracking}>Jarak</Text>);

        expect(screen.getByText('Jarak')).toHaveClass(utility);
    });

    it('truncates to one line', () => {
        render(<Text truncate>Nama paroki yang sangat panjang</Text>);

        expect(screen.getByText('Nama paroki yang sangat panjang')).toHaveClass(
            'truncate',
        );
    });

    it.each([1, 2, 3, 4, 5, 6] as const)(
        'clamps to %s lines with a literal class',
        (lines) => {
            render(<Text lineClamp={lines}>Potong</Text>);

            // The literal matters: an interpolated `line-clamp-${n}` is
            // invisible to Tailwind's scanner and would emit no CSS at all.
            expect(screen.getByText('Potong')).toHaveClass(
                `line-clamp-${lines}`,
            );
        },
    );

    /**
     * Both set overflow, so passing both would leave the winner up to
     * stylesheet order. lineClamp wins, and it does so on every page.
     */
    it('prefers lineClamp over truncate when both are given', () => {
        render(
            <Text lineClamp={2} truncate>
                Dua-duanya
            </Text>,
        );

        const element = screen.getByText('Dua-duanya');

        expect(element).toHaveClass('line-clamp-2');
        expect(element).not.toHaveClass('truncate');
    });

    it.each([
        ['words', 'break-words'],
        ['all', 'break-all'],
        ['keep', 'break-keep'],
        ['normal', 'break-normal'],
    ] as const)('applies break "%s"', (wrap, utility) => {
        render(<Text break={wrap}>Pemenggalan</Text>);

        expect(screen.getByText('Pemenggalan')).toHaveClass(utility);
    });

    it.each([
        ['nowrap', 'whitespace-nowrap'],
        ['pre-line', 'whitespace-pre-line'],
        ['pre-wrap', 'whitespace-pre-wrap'],
        ['break-spaces', 'whitespace-break-spaces'],
    ] as const)('applies whitespace "%s"', (whitespace, utility) => {
        render(<Text whitespace={whitespace}>Ruang</Text>);

        expect(screen.getByText('Ruang')).toHaveClass(utility);
    });

    /**
     * Composes with the rest of the application without absorbing any of it.
     * An icon is a sibling, not a child prop.
     */
    it('composes with an icon and a link', () => {
        render(
            <Text variant="caption-strong" className="flex items-center gap-2">
                <span aria-hidden="true">📍</span>
                <a href="/kontak">Kontak</a>
            </Text>,
        );

        expect(screen.getByRole('link', { name: 'Kontak' })).toHaveAttribute(
            'href',
            '/kontak',
        );
        expect(screen.getByText('📍')).toHaveAttribute('aria-hidden', 'true');
    });
});
