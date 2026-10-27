import { Text } from '@/components/text';
import type { ContactDetails } from '@/types';
import FacebookIcon from '@/components/icons/facebook-icon';
import InstagramIcon from '@/components/icons/instagram-icon';
import YoutubeIcon from '@/components/icons/youtube-icon';
import WhatsAppIcon from '@/components/icons/whatsapp-icon';

type SocialItem = {
    key: 'facebook' | 'instagram' | 'youtube' | 'whatsappChannel';
    label: string;
    Icon: any;
};

const SOCIALS: SocialItem[] = [
    {
        key: 'facebook',
        label: 'Facebook',
        Icon: FacebookIcon,
    },
    {
        key: 'instagram',
        label: 'Instagram',
        Icon: InstagramIcon,
    },
    {
        key: 'youtube',
        label: 'YouTube',
        Icon: YoutubeIcon,
    },
    {
        key: 'whatsappChannel',
        label: 'Kanal WhatsApp',
        Icon: WhatsAppIcon,
    },
];

type FooterSocialProps = {
    contact: ContactDetails | undefined;
};

export function FooterSocial({ contact }: FooterSocialProps) {
    const items = SOCIALS.flatMap((item) => {
        const href = contact?.social[item.key];
        return href ? [{ ...item, href }] : [];
    });

    if (items.length === 0) {
        return null;
    }

    return (
        <section aria-label="Media Sosial">
            <Text
                as="h2"
                variant="label"
                color="foreground"
                className="relative inline-flex pb-2"
            >
                Media Sosial
            </Text>
            <ul
                className="mt-3 flex flex-wrap gap-3"
                aria-label="Media sosial paroki"
            >
                {items.map(({ key, label, Icon, href }) => (
                    <li key={key}>
                        <a
                            href={href}
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label={label}
                            className={`flex size-8 items-center justify-center rounded-full text-white shadow-sm transition-transform hover:-translate-y-0.5 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none`}
                        >
                            <Icon className="size-4" aria-hidden="true" />
                        </a>
                    </li>
                ))}
            </ul>
        </section>
    );
}
