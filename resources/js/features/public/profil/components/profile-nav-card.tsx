import { Link, type InertiaLinkProps } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { Text } from '@/components/text';

/*
 * One navigation card on /profil. A quiet bordered surface, not a heavy card:
 * DESIGN.md wants whitespace to do the work, and four identical feature cards
 * is the layout cliché this avoids by keeping the surface to a single hairline.
 */
export function ProfileNavCard({
    title,
    description,
    href,
}: {
    title: string;
    description: string;
    href: InertiaLinkProps['href'];
}) {
    return (
        <Link
            href={href}
            className="group flex flex-col justify-between gap-6 rounded-lg border border-hairline bg-background p-6 transition-colors hover:border-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
        >
            <div className="space-y-2">
                <Text as="h3" variant="body-strong">
                    {title}
                </Text>
                <Text as="p" variant="caption" color="muted">
                    {description}
                </Text>
            </div>

            <Text
                as="span"
                variant="caption-strong"
                className="inline-flex items-center gap-1 text-primary"
            >
                Buka
                <ArrowRight
                    className="size-4 transition-transform group-hover:translate-x-0.5"
                    aria-hidden="true"
                />
            </Text>
        </Link>
    );
}

export default ProfileNavCard;
