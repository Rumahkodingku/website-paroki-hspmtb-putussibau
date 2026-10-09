import { cva, type VariantProps } from 'class-variance-authority';
import type {
    ComponentPropsWithoutRef,
    CSSProperties,
    ElementType,
    ReactNode,
} from 'react';
import { cn } from '@/lib/utils';

/*
 * Text - the foundation for every piece of text in the application.
 *
 * DESIGN.md's typography block lists sixteen steps, and resources/css/app.css
 * declares all sixteen as Tailwind `--text-*` tokens, each carrying its own
 * size, line height, letter spacing and weight. Before this component those
 * tokens were reachable only by writing the class by hand, which is how
 * `font-display text-display-md` ended up written out in four separate files
 * and `text-caption-strong text-red-on-surface` in two more. Every one of those
 * copies is a place the design system can drift from.
 *
 * So this component owns the mapping from a role to a token and nothing else.
 *
 * Three rules govern the API, and all three exist to stop a specific mistake:
 *
 *   1. `variant` is the way to choose typography. The override props exist for
 *      the cases that genuinely need one, and they are all constrained to
 *      utilities the design system already allows. There is no `size` prop
 *      accepting an arbitrary value, because that would be a way around the
 *      token scale rather than an addition to it.
 *
 *   2. `variant` and `as` are independent. Style comes from `variant`; the
 *      element comes from `as`. Changing `as` never changes `variant`, and the
 *      default element is derived from the variant rather than the other way
 *      round - see DEFAULT_ELEMENT below.
 *
 *   3. No layout, no icons, no interaction. This renders text and nothing
 *      else. A caller that needs a button uses Button, which keeps its own
 *      typography because a button's size is part of its hit target.
 *
 * @see docs/DESIGN.md "Typography"
 * @see docs/DECISIONS.md D-33
 */

/**
 * The sixteen tokens, as role names.
 *
 * DESIGN.md defines display steps, lead, tagline, body, caption, button and
 * navigation roles. It never defines heading LEVELS, so `h1` through `h6` are
 * mapped here onto the steps that already existed rather than given tokens of
 * their own. That mapping is the reason `h1` is 40px rather than the 34px the
 * public pages used before: h1 through h3 all shared `display-md`, which meant
 * the document outline carried no visual hierarchy at all.
 *
 * Note that `h1` and `display-lg` are the same typography, as are `h2` and
 * `display-md`. That is deliberate and it is the pair worth reading together:
 * the display-* names are the scale step a caller reaches for when they want
 * that size and the element is something else, while h1..h6 are the semantic
 * anchor. Asking for `h3` and getting 21px is the point; asking for `h2` and
 * getting 34px is unchanged.
 */
const textVariants = cva('', {
    variants: {
        variant: {
            /* DESIGN.md typography.hero-display - 56px, the homepage hero. */
            hero: 'font-display text-hero-display text-balance',

            /*
             * The two display steps, and the three heading steps above them.
             *
             * font-display is applied here rather than left to the caller
             * because DESIGN.md's font family section is explicit that SF Pro
             * Display is for hero and large editorial headings, and leaving it
             * optional is how the same size ends up rendered in two families.
             *
             * text-balance is here for the same reason. Every display heading
             * in the application was carrying it as a hand-written class - four
             * separate copies of `className="text-balance"` - because a
             * 34-to-56px heading is exactly where the browser's default line
             * breaking produces the orphan that reads as a layout bug. A
             * caller who disagrees can pass `className="text-pretty"`, which
             * tailwind-merge resolves against text-balance as the later value.
             */
            'display-lg': 'font-display text-display-lg text-balance',
            'display-md': 'font-display text-display-md text-balance',
            h1: 'font-display text-display-lg text-balance',
            h2: 'font-display text-display-md text-balance',

            /*
             * h3 and below are left un-balanced deliberately. At 21px and under
             * a heading is one or two lines and text-balance mostly does
             * nothing, so carrying it would be a class that exists to be
             * overridden.
             */
            h3: 'font-display text-tagline',
            h4: 'text-body-strong',
            h5: 'text-caption-strong',
            h6: 'text-caption-strong',

            /* The remaining roles, one token each. */
            lead: 'text-lead',
            'lead-airy': 'text-lead-airy',
            tagline: 'font-display text-tagline',
            body: 'text-body',
            'body-strong': 'text-body-strong',
            caption: 'text-caption',
            'caption-strong': 'text-caption-strong',

            /*
             * typography.nav-link is 13px with a line height of 1.0, which is
             * tuned for a link inside a bar rather than for a line of prose.
             * It is offered under the name `nav` because that is what it is
             * for; a caller reaching for body copy wants `body`.
             */
            nav: 'text-nav-link',

            /* Footer and legal. */
            'fine-print': 'text-fine-print',
            'micro-legal': 'text-micro-legal',

            /*
             * Identical to the `.rich-text blockquote` rule in app.css, on
             * purpose rather than by coincidence: a quote typed into the
             * editor and a quote rendered as a component have to look the
             * same, or the administrator previews one thing and the visitor
             * gets another. The dark override is not optional - text-navy on
             * the dark canvas measures 1.25:1.
             */
            blockquote:
                'border-s-4 border-primary ps-4 text-navy italic dark:text-navy-light',
        },

        /*
         * Semantic text colours.
         *
         * There is deliberately no `success` and no `warning`. DESIGN.md has no
         * green and no amber in its palette, and D-22 already removed the
         * `text-green-600` success messages for exactly that reason. Adding
         * either here would put an undocumented colour into the design system
         * through the back door.
         *
         * `default` emits no class at all rather than `text-foreground`, so a
         * Text inside a surface that sets its own colour - bg-navy with
         * text-white, as ParishCta does - inherits it instead of fighting it.
         */
        color: {
            default: '',
            foreground: 'text-foreground',
            muted: 'text-muted-foreground',
            subtle: 'text-ink-muted-soft',

            /*
             * navy as TEXT, and the dark override is not optional: navy is
             * #01266D and measures 1.25:1 against the dark canvas, which is
             * invisible rather than subtle. navy-light is the same ramp read
             * the other way and measures 15.24:1.
             *
             * Carried inside the variant rather than left to the caller
             * because four auth and account pages were already writing the
             * pair by hand, and a caller who remembers it on one page and not
             * the next gets unreadable text on one screen of a flow.
             *
             * This is the same split red-on-surface exists for, one hue down.
             */
            brand: 'text-navy dark:text-navy-light',
            'brand-inverse': 'text-navy-light',
            danger: 'text-red-on-surface',
            inherit: 'text-inherit',
        },

        align: {
            left: 'text-left',
            center: 'text-center',
            right: 'text-right',
            justify: 'text-justify',
            start: 'text-start',
            end: 'text-end',
        },

        transform: {
            none: 'normal-case',
            uppercase: 'uppercase',
            lowercase: 'lowercase',
            capitalize: 'capitalize',
        },

        decoration: {
            none: 'no-underline',
            underline: 'underline',
            overline: 'overline',
            'line-through': 'line-through',
        },

        /*
         * Overrides.
         *
         * Every value below is a Tailwind utility the design system already
         * uses elsewhere, so an override cannot introduce a size, weight or
         * colour that is not already in the project. That is the whole
         * constraint: this is a convenience over the existing vocabulary, not
         * a second vocabulary.
         *
         * `weight` deliberately stops at semibold. DESIGN.md: "Weight 600 is
         * the main heading emphasis; avoid unnecessary 700-heavy typography."
         * There is no bold.
         */
        weight: {
            light: 'font-light',
            normal: 'font-normal',
            medium: 'font-medium',
            semibold: 'font-semibold',
        },

        leading: {
            none: 'leading-none',
            tight: 'leading-tight',
            snug: 'leading-snug',
            normal: 'leading-normal',
            relaxed: 'leading-relaxed',
            loose: 'leading-loose',
        },

        tracking: {
            tighter: 'tracking-tighter',
            tight: 'tracking-tight',
            normal: 'tracking-normal',
            wide: 'tracking-wide',
            wider: 'tracking-wider',
            widest: 'tracking-widest',
        },

        /*
         * Overflow.
         *
         * `lineClamp` is a union of numbers rather than `number`, and it maps
         * to literal class names. An interpolated `line-clamp-${n}` is invisible
         * to Tailwind's scanner, which reads source text rather than runtime
         * values, so it would silently produce no CSS at all. Six is as far as
         * any excerpt in this design runs; beyond that a card is the wrong
         * component.
         */
        lineClamp: {
            1: 'line-clamp-1',
            2: 'line-clamp-2',
            3: 'line-clamp-3',
            4: 'line-clamp-4',
            5: 'line-clamp-5',
            6: 'line-clamp-6',
        },

        break: {
            normal: 'break-normal',
            words: 'break-words',
            all: 'break-all',
            keep: 'break-keep',
        },

        whitespace: {
            normal: 'whitespace-normal',
            nowrap: 'whitespace-nowrap',
            pre: 'whitespace-pre',
            'pre-line': 'whitespace-pre-line',
            'pre-wrap': 'whitespace-pre-wrap',
            'break-spaces': 'whitespace-break-spaces',
        },
    },
    defaultVariants: {
        variant: 'body',
        color: 'default',
    },
});

export type TextVariantProps = VariantProps<typeof textVariants>;

/**
 * The element a variant renders when `as` is not given.
 *
 * `span` is the default for everything that is not a heading or a quote,
 * because it is the only element that is valid in every position a piece of
 * text can appear: inside a `<p>`, inside a heading, inside a `<label>`, and
 * beside another span. Defaulting to `<p>` would make `<Text variant="caption">`
 * inside a paragraph invalid HTML, and defaulting to `<div>` would break the
 * same way inside a heading.
 *
 * Headings default to their own tag so that `variant="h3"` cannot quietly
 * render a span styled like a heading. That is a heading which is invisible to
 * a screen reader navigating by heading level, and it is the specific mistake
 * the polymorphic API is most able to cause. `as` overrides all of it without
 * touching `variant`.
 */
const DEFAULT_ELEMENT = {
    hero: 'h1',
    'display-lg': 'h2',
    'display-md': 'h2',
    h1: 'h1',
    h2: 'h2',
    h3: 'h3',
    h4: 'h4',
    h5: 'h5',
    h6: 'h6',
    blockquote: 'blockquote',
} as const satisfies Partial<
    Record<NonNullable<TextVariantProps['variant']>, ElementType>
>;

type TextOwnProps = TextVariantProps & {
    children?: ReactNode;
    className?: string;
    style?: CSSProperties;
    /** Shrink to one line with an ellipsis. Superseded by lineClamp. */
    truncate?: boolean;
    italic?: boolean;
};

/**
 * Props for one element.
 *
 * `as` is declared here rather than inside TextOwnProps, and that placement is
 * load-bearing. TypeScript has to infer `T` from it: if `as` were typed as the
 * broad `ElementType` it would never narrow, `T` would fall back to its
 * `'span'` default on every call, and `<Text as="label" htmlFor="name">` would
 * be rejected because a span has no htmlFor - which was exactly the first
 * version of this file and the reason the type is shaped this way.
 *
 * The element's own attributes are intersected in and Text's props are
 * subtracted back out, so `as="label"` accepts `htmlFor`, `as="p"` rejects
 * `href`, and neither needs a cast at the call site.
 */
export type TextProps<T extends ElementType = 'span'> = {
    /** The element to render. Decides semantics; never changes the variant. */
    as?: T;
} & TextOwnProps &
    Omit<ComponentPropsWithoutRef<T>, keyof TextOwnProps | 'as'>;

export function Text<T extends ElementType = 'span'>({
    as,
    variant,
    color,
    align,
    transform,
    decoration,
    weight,
    leading,
    tracking,
    lineClamp,
    break: wrap,
    whitespace,
    truncate = false,
    italic = false,
    className,
    style,
    children,
    ...rest
}: TextProps<T>) {
    /*
     * lineClamp supersedes truncate rather than competing with it. Both set
     * overflow, and Tailwind emits them in source order, so passing both would
     * leave the winner up to the stylesheet. Picking one here makes the
     * outcome the same on every page.
     */
    const clamped = lineClamp !== null && lineClamp !== undefined;

    /*
     * The one cast in the file, and it is unavoidable.
     *
     * `T` is still generic at this point - it has not been resolved to the
     * literal the caller wrote - so TypeScript cannot verify that `rest`
     * matches ComponentPropsWithoutRef<T>. Every polymorphic component has this
     * boundary, and the usual alternatives are worse: createElement loses the
     * props entirely, and duplicating the signature into a discriminated union
     * over twenty elements buys nothing a caller can feel.
     *
     * What protects the call site is TextProps above, not this cast: the
     * attributes were already checked against the right element before the
     * component body ran.
     */
    const Element = (as ??
        DEFAULT_ELEMENT[(variant ?? 'body') as keyof typeof DEFAULT_ELEMENT] ??
        'span') as ElementType;

    return (
        <Element
            className={cn(
                textVariants({
                    variant,
                    color,
                    align,
                    transform,
                    decoration,
                    weight,
                    leading,
                    tracking,
                    lineClamp,
                    break: wrap,
                    whitespace,
                }),
                italic && 'italic',
                truncate && !clamped && 'truncate',
                className,
            )}
            style={style}
            {...rest}
        >
            {children}
        </Element>
    );
}

export default Text;
