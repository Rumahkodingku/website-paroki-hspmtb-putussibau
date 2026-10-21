import { cva, type VariantProps } from 'class-variance-authority';
import type {
    ComponentPropsWithoutRef,
    CSSProperties,
    ElementType,
    ReactNode,
} from 'react';

import { cn } from '@/lib/utils';

/**
 * Text
 *
 * Shared typography primitive for the application.
 *
 * Design principles:
 * - Typography variants map to existing design-system tokens.
 * - HTML semantics are independent from visual typography.
 * - Semantic colors use existing theme tokens.
 * - Overrides remain constrained to predefined Tailwind utilities.
 * - No layout, icons, or interactive behavior.
 */
const textVariants = cva('', {
    variants: {
        variant: {
            // Display typography
            hero: 'font-display text-display-xl text-balance',
            'display-xl': 'font-display text-display-xl text-balance',
            'display-lg': 'font-display text-display-lg text-balance',
            'display-md': 'font-display text-display-md text-balance',

            // Semantic heading typography
            h1: 'font-display text-display-lg text-balance',
            h2: 'font-display text-display-md text-balance',
            h3: 'font-display text-heading-lg',
            h4: 'text-heading-md',
            h5: 'text-heading-sm',
            h6: 'text-heading-sm',

            // Visual heading aliases
            'heading-lg': 'font-display text-heading-lg',
            'heading-md': 'font-display text-heading-md',
            'heading-sm': 'text-heading-sm',

            // Body typography
            'body-lg': 'text-body-lg',
            body: 'text-body',
            'body-sm': 'text-body-sm',

            // Supporting typography
            label: 'text-label',
            nav: 'text-label',
            'fine-print': 'text-fine-print',

            // Convenience aliases using existing typography tokens
            caption: 'text-fine-print',
            overline: 'text-label tracking-wide uppercase',

            // Editorial typography
            blockquote:
                'border-s-4 border-primary ps-4 text-navy italic dark:text-navy-soft',

            // Technical content
            code: 'font-mono text-body-sm',
        },

        color: {
            /**
             * Inherit the color from the surrounding context.
             * Useful on surfaces with their own foreground color.
             */
            default: '',

            foreground: 'text-foreground',
            muted: 'text-muted-foreground',
            subtle: 'text-subtle',

            // Brand colors
            brand: 'text-navy dark:text-navy-soft',
            'brand-inverse': 'text-navy-soft',

            // Semantic aliases using existing theme tokens
            primary: 'text-foreground',
            secondary: 'text-muted-foreground',

            /**
             * Keep the existing project mapping for danger.
             * Replace this mapping only if a dedicated danger token
             * is defined and approved in the design system.
             */
            danger: 'text-primary-on-surface',

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

        /**
         * Use literal class names so Tailwind can discover
         * every supported line-clamp utility.
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
 * Default HTML element for variants that have semantic meaning.
 *
 * The remaining variants render as span by default so they can
 * safely appear inside paragraphs, headings, labels, and other
 * phrasing-content contexts.
 */
const DEFAULT_ELEMENT = {
    hero: 'h1',
    'display-xl': 'h1',
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

    /**
     * Truncate text to one line with an ellipsis.
     * Ignored when lineClamp is provided.
     */
    truncate?: boolean;

    /**
     * Italicize the text.
     */
    italic?: boolean;
};

/**
 * Polymorphic Text props.
 *
 * `as` controls HTML semantics.
 * `variant` controls visual typography.
 *
 * The rendered element's native props remain type-safe.
 */
export type TextProps<T extends ElementType = 'span'> = {
    as?: T;
} & TextOwnProps &
    Omit<ComponentPropsWithoutRef<T>, keyof TextOwnProps | 'as'>;

export function Text<const T extends ElementType = 'span'>({
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
    break: wordBreak,
    whitespace,
    truncate = false,
    italic = false,
    className,
    style,
    children,
    ...rest
}: TextProps<T>) {
    const hasLineClamp = lineClamp !== null && lineClamp !== undefined;

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
                    break: wordBreak,
                    whitespace,
                }),
                italic && 'italic',
                truncate && !hasLineClamp && 'truncate',
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
