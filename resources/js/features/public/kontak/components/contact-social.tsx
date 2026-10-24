import { ArrowUpRight } from 'lucide-react';
import type { ComponentType, SVGProps } from 'react';

import FacebookIcon from '@/components/icons/facebook-icon';
import InstagramIcon from '@/components/icons/instagram-icon';
import WhatsAppIcon from '@/components/icons/whatsapp-icon';
import YoutubeIcon from '@/components/icons/youtube-icon';
import { ParishCard, ParishCardContent } from '@/components/parish-card';
import { Text } from '@/components/text';
import type { ContactDetails } from '@/types';

type SocialIcon = ComponentType<SVGProps<SVGSVGElement>>;

type SocialLink = {
    key: keyof ContactDetails['social'];
    label: string;
    description: string;
    icon: SocialIcon;
};

const LINKS: SocialLink[] = [
    {
        key: 'facebook',
        label: 'Facebook',
        description: 'Paroki HSPMTB Putussibau',
        icon: FacebookIcon,
    },
    {
        key: 'instagram',
        label: 'Instagram',
        description: '@parokihspmtb',
        icon: InstagramIcon,
    },
    {
        key: 'youtube',
        label: 'YouTube',
        description: 'Kanal resmi paroki',
        icon: YoutubeIcon,
    },
    {
        key: 'whatsappChannel',
        label: 'Kanal WhatsApp',
        description: 'Pengumuman dan informasi paroki',
        icon: WhatsAppIcon,
    },
];

export function ContactSocial({
    social,
}: {
    social: ContactDetails['social'];
}) {
    const items = LINKS.flatMap((item) => {
        const href = social[item.key];

        return href ? [{ ...item, href }] : [];
    });

    if (items.length === 0) {
        return null;
    }

    return (
        <div className="grid gap-3 sm:grid-cols-2">
            {items.map((item) => {
                const Icon = item.icon;

                return (
                    <ParishCard
                        key={item.key}
                        className="border-none bg-transparent py-0 shadow-none transition-[border-color,box-shadow,transform] duration-200 hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-sm"
                    >
                        <ParishCardContent className="p-0">
                            <a
                                href={item.href}
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label={item.label}
                                className="group flex min-h-20 items-center gap-3 p-4 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                <span className="flex size-10 shrink-0 items-center justify-center text-primary-on-surface">
                                    <Icon
                                        aria-hidden="true"
                                        className="size-6"
                                    />
                                </span>

                                <span className="min-w-0 flex-1">
                                    <Text
                                        as="span"
                                        variant="label"
                                        color="foreground"
                                        className="block font-semibold"
                                    >
                                        {item.label}
                                    </Text>

                                    <Text
                                        as="span"
                                        variant="fine-print"
                                        color="muted"
                                        className="mt-0.5 block truncate"
                                    >
                                        {item.description}
                                    </Text>
                                </span>

                                <ArrowUpRight
                                    aria-hidden="true"
                                    className="size-4 shrink-0 text-primary-on-surface transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5"
                                />
                            </a>
                        </ParishCardContent>
                    </ParishCard>
                );
            })}
        </div>
    );
}

export default ContactSocial;
