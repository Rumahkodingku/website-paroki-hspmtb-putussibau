import { Text } from '@/components/text';

/**
 * The type contract for Text, checked by `npm run types:check`.
 *
 * These are not runtime tests. This file compiles and is never imported, which
 * is why it is named .types.tsx and not .test.tsx: Vitest's include glob would
 * collect a .test.tsx here and fail it for containing no test.
 *
 * Every `@ts-expect-error` below is a claim about what the compiler must
 * REJECT, and the claim fails loudly - as an "unused @ts-expect-error
 * directive" - the moment the type stops being that strict. That is the only
 * way to assert a negative about a type system: a runtime assertion cannot see
 * a type at all.
 *
 * The rejecting cases are self-closing on purpose. A `@ts-expect-error`
 * suppresses the error on the line immediately below it, and the formatter
 * breaks a JSX element with children across three lines - which moves the
 * error two lines down and leaves the directive unused, so `tsc` reports the
 * error twice over and the file cannot be formatted.
 *
 * @see docs/DECISIONS.md D-33
 */

/*
 * `as` must narrow to the element that was asked for.
 *
 * The failure this pins is specific. An earlier shape of TextProps typed `as`
 * as the broad `ElementType` inside the shared props object, so `T` fell back
 * to its `'span'` default on every call and `<Text as="label" htmlFor="name">`
 * was rejected because a span has no htmlFor. TypeScript can only infer `T`
 * from `as` when `as` is declared as `T` on the props type that call sites
 * actually see.
 */
export const LabelAcceptsHtmlFor = (
    <Text as="label" variant="caption-strong" htmlFor="nama">
        Nama
    </Text>
);

export const AnchorAcceptsHref = (
    <Text as="a" href="/kontak">
        Kontak
    </Text>
);

export const TimeAcceptsDateTime = (
    <Text as="time" dateTime="2026-01-01">
        1 Januari
    </Text>
);

export const BlockquoteVariantRendersItsOwnTag = (
    <Text variant="blockquote">Kutipan</Text>
);

/*
 * And it must REJECT attributes belonging to a different element. Without this
 * the props are simply wide enough for any attribute and the generic buys
 * nothing - every caller would need a cast for htmlFor, and that cast would be
 * the only thing standing between the codebase and `<Text as="p" href>`.
 */

// @ts-expect-error a <p> has no href.
export const ParagraphRejectsHref = <Text as="p" href="/x" />;

// @ts-expect-error a <label> has no href.
export const LabelRejectsHref = <Text as="label" href="/x" />;

// @ts-expect-error the default element is a span, which has no htmlFor.
export const DefaultRejectsHtmlFor = <Text htmlFor="x" />;

// @ts-expect-error a <div> has no type attribute; that is a button's.
export const DivRejectsType = <Text as="div" type="button" />;

/*
 * The variant, colour and override vocabularies are closed.
 *
 * There is no `success` and no `warning` colour: DESIGN.md has no green or
 * amber in its palette, and D-22 removed the text-green-600 success messages
 * for that reason. A closed union is what keeps them from being reintroduced
 * through a convenient-looking escape hatch.
 */

// @ts-expect-error no such variant; the scale is the sixteen DESIGN.md tokens.
export const RejectsUnknownVariant = <Text variant="display-xl" />;

// @ts-expect-error no success colour exists in this design system.
export const RejectsSuccessColour = <Text color="success" />;

// @ts-expect-error lineClamp is 1..6; an interpolated class would emit no CSS.
export const RejectsWideLineClamp = <Text lineClamp={12} />;

// @ts-expect-error DESIGN.md forbids 700-heavy typography, so there is no bold.
export const RejectsBoldWeight = <Text weight="bold" />;
