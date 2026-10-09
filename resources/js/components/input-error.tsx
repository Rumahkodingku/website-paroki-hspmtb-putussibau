import type { ComponentProps } from 'react';
import { Text } from '@/components/text';

/*
 * A validation message, and the only red text on an admin page.
 *
 * red-on-surface rather than text-destructive or text-primary: the brand red is
 * correct for a fill and wrong for text on the dark canvas, where it measures
 * 2.28:1. See the token's own note in app.css and D-32.
 *
 * The element and the props are generic over `p` rather than hard-coded to
 * HTMLParagraphElement so the caller keeps whatever attributes it was passing
 * to the previous implementation.
 */
type InputErrorProps = ComponentProps<typeof Text<'p'>> & {
    message?: string;
};

export default function InputError({ message, ...props }: InputErrorProps) {
    return message ? (
        <Text as="p" variant="caption" color="danger" {...props}>
            {message}
        </Text>
    ) : null;
}
