import { Text } from '@/components/text';

/*
 * The admin section heading.
 *
 * Its API is unchanged - title, description, variant - because five pages
 * across three features call it and none of them should have to care that the
 * typography underneath moved. What changed is where the sizes come from.
 *
 * These two steps used Tailwind's scale directly (text-xl, text-base, text-sm),
 * which is the one place in the application where typography was chosen
 * outside the design system. DESIGN.md has no admin-specific scale, so both
 * are mapped onto tokens that already exist:
 *
 *   default  text-xl  20px/600  ->  tagline  21px/600
 *   small    text-base 16px/500 ->  body-strong 17px/600 + medium
 *
 * Both are within a pixel of what they replace. What they buy is that the
 * tracking and line height now come from the same table as the public pages,
 * and that a fourth heading added later has an obvious answer.
 *
 * The admin keeps a visibly smaller scale than the public site on purpose.
 * An admin page is a working surface, not an editorial one, and DESIGN.md's
 * display steps would make a settings panel as loud as a homepage.
 */
export default function Heading({
    title,
    description,
    variant = 'default',
}: {
    title: string;
    description?: string;
    variant?: 'default' | 'small';
}) {
    return (
        <header className={variant === 'small' ? '' : 'mb-8 space-y-0.5'}>
            <Text
                as="h2"
                variant={variant === 'small' ? 'body-strong' : 'tagline'}
                weight={variant === 'small' ? 'medium' : undefined}
                className={variant === 'small' ? 'mb-0.5' : undefined}
            >
                {title}
            </Text>
            {description && (
                <Text as="p" variant="caption" color="muted">
                    {description}
                </Text>
            )}
        </header>
    );
}
