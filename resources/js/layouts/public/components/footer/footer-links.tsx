import { Link } from '@inertiajs/react';
import { Text } from '@/components/text';
import type { FooterGroup } from '@/layouts/public/components/footer/footer-data';
import { LINK_CLASS } from '@/layouts/public/components/footer/footer-data';

export function FooterLinks({ group }: { group: FooterGroup }) {
    return (
        <section aria-label={group.title}>
            <Text
                as="h2"
                variant="label"
                color="foreground"
                className="relative inline-flex"
            >
                {group.title}
            </Text>
            <ul className="mt-3 space-y-0.5">
                {group.links.map((item) => (
                    <li key={`${group.title}-${item.label}`}>
                        <Link href={item.href} className={LINK_CLASS}>
                            {item.label}
                        </Link>
                    </li>
                ))}
            </ul>
        </section>
    );
}
