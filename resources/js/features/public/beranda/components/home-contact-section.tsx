import { Link } from '@inertiajs/react';
import {
    ArrowRight,
    Clock,
    Mail,
    MapPin,
    MessageCircle,
    Phone,
} from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { index as kontak } from '@/routes/kontak';
import type { ContactDetails } from '@/types';

/**
 * The homepage's contact summary.
 *
 * `home_show_contact` gates this block and nothing else: the /kontak page is
 * always reachable, so turning the block off removes a shortcut rather than
 * the destination (KON-12).
 *
 * This is deliberately a compact summary rather than the full /kontak
 * component. The two live in different features and a feature may not import
 * another feature (ARCHITECTURE.md Part B §4); keeping the homepage block small
 * also matches the "one block per section" shape the current homepage has.
 */

type HomeContactItem = {
    key: string;
    icon: LucideIcon;
    label: string;
    value: string;
    href?: string;
    external?: boolean;
};

export function HomeContactSection({ contact }: { contact: ContactDetails }) {
    if (!contact.homeShowContact) {
        return null;
    }

    const items: HomeContactItem[] = [];

    if (contact.address) {
        items.push({
            key: 'address',
            icon: MapPin,
            label: 'Alamat',
            value: contact.address,
        });
    }

    if (contact.phone) {
        items.push({
            key: 'phone',
            icon: Phone,
            label: 'Telepon',
            value: contact.phone.display,
            href: contact.phone.tel,
        });
    }

    if (contact.whatsapp) {
        items.push({
            key: 'whatsapp',
            icon: MessageCircle,
            label: 'WhatsApp',
            value: contact.whatsapp.display ?? 'Chat WhatsApp',
            href: contact.whatsapp.url,
            external: true,
        });
    }

    if (contact.email) {
        items.push({
            key: 'email',
            icon: Mail,
            label: 'Surel',
            value: contact.email,
            href: `mailto:${contact.email}`,
        });
    }

    /*
     * The weekly schedule, already grouped into lines by
     * App\Support\OfficeHours::format() on the server. Same value the /kontak
     * page shows, so the two cannot drift; the footer deliberately keeps only
     * the link to /kontak because it is a fine-print navigation column.
     */
    if (contact.officeHours) {
        items.push({
            key: 'office-hours',
            icon: Clock,
            label: 'Jam Layanan',
            value: contact.officeHours,
        });
    }

    if (items.length === 0) {
        return null;
    }

    return (
        <PublicSection surface="soft">
            <div className="space-y-8">
                <SectionHeading
                    eyebrow="Kontak"
                    title="Hubungi Kami"
                    description="Alamat dan kanal komunikasi sekretariat paroki."
                    action={
                        <Button asChild variant="outline">
                            <Link href={kontak()}>
                                Halaman Kontak
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                    }
                />

                <ul className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {items.map((item) => {
                        const Icon = item.icon;
                        const value = (
                            <Text
                                as="span"
                                variant="body"
                                color="muted"
                                className="break-words whitespace-pre-line"
                            >
                                {item.value}
                            </Text>
                        );

                        return (
                            <li
                                key={item.key}
                                className="flex items-start gap-3"
                            >
                                <Icon
                                    className="mt-0.5 size-5 shrink-0 text-red-on-surface"
                                    aria-hidden="true"
                                />

                                <div className="space-y-1">
                                    <Text
                                        as="p"
                                        variant="caption-strong"
                                        color="foreground"
                                    >
                                        {item.label}
                                    </Text>

                                    {item.href ? (
                                        <a
                                            href={item.href}
                                            {...(item.external
                                                ? {
                                                      target: '_blank',
                                                      rel: 'noopener noreferrer',
                                                  }
                                                : {})}
                                            className="transition-colors hover:text-red-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background focus-visible:outline-none"
                                        >
                                            {value}
                                        </a>
                                    ) : (
                                        value
                                    )}
                                </div>
                            </li>
                        );
                    })}
                </ul>
            </div>
        </PublicSection>
    );
}

export default HomeContactSection;
