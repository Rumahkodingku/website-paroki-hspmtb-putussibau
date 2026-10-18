import { Facebook, Instagram, MessageCircle, Youtube } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { ContactDetails } from '@/types';

/**
 * The social channels that are actually configured.
 *
 * A channel with no URL is not rendered at all rather than rendered disabled:
 * a dead social icon is a promise the parish has not made. The server has
 * already refused non-http values, so the href here is always a real link.
 */

const LINKS: {
    key: keyof ContactDetails['social'];
    label: string;
    icon: LucideIcon;
}[] = [
    { key: 'facebook', label: 'Facebook', icon: Facebook },
    { key: 'instagram', label: 'Instagram', icon: Instagram },
    { key: 'youtube', label: 'YouTube', icon: Youtube },
    { key: 'whatsappChannel', label: 'Kanal WhatsApp', icon: MessageCircle },
];

export function ContactSocial({
    social,
}: {
    social: ContactDetails['social'];
}) {
    const items = LINKS.flatMap((link) => {
        const href = social[link.key];

        return href === null ? [] : [{ ...link, href }];
    });

    if (items.length === 0) {
        return null;
    }

    return (
        <div className="flex flex-wrap gap-3">
            {items.map((item) => {
                const Icon = item.icon;

                return (
                    <Button key={item.key} asChild variant="outline">
                        <a
                            href={item.href}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <Icon aria-hidden="true" />
                            {item.label}
                        </a>
                    </Button>
                );
            })}
        </div>
    );
}

export default ContactSocial;
