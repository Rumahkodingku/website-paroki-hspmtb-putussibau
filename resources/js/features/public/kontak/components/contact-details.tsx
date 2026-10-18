import { Clock, Mail, MapPin, MessageCircle, Phone } from 'lucide-react';
import type { LucideIcon } from 'lucide-react';
import { ParishCard, ParishCardContent } from '@/components/parish-card';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import type { ContactDetails as ContactDetailsData } from '@/types';
import { CopyButton } from './copy-button';

/**
 * The contact cards the parish has actually configured.
 *
 * Every item is conditional. A missing phone produces no card and no `tel:`
 * link, which is the only honest rendering: an empty anchor looks like a
 * working button to a screen reader and goes nowhere when tapped (KON-10).
 */

type ContactItem = {
    key: string;
    label: string;
    icon: LucideIcon;
    value: string;
    href?: string;
    actionLabel?: string;
    external?: boolean;
    copy?: { value: string; label: string };
};

export function ContactDetails({ contact }: { contact: ContactDetailsData }) {
    const items: ContactItem[] = [];

    if (contact.address) {
        items.push({
            key: 'address',
            label: 'Alamat',
            icon: MapPin,
            value: contact.address,
            copy: { value: contact.address, label: 'Salin alamat' },
        });
    }

    if (contact.phone) {
        items.push({
            key: 'phone',
            label: 'Telepon',
            icon: Phone,
            value: contact.phone.display,
            href: contact.phone.tel,
            actionLabel: 'Telepon',
            copy: {
                value: contact.phone.display,
                label: 'Salin nomor telepon',
            },
        });
    }

    if (contact.whatsapp) {
        items.push({
            key: 'whatsapp',
            label: 'WhatsApp',
            icon: MessageCircle,
            value: contact.whatsapp.display ?? 'Chat WhatsApp',
            href: contact.whatsapp.url,
            actionLabel: 'Chat WhatsApp',
            external: true,
        });
    }

    if (contact.email) {
        items.push({
            key: 'email',
            label: 'Surel',
            icon: Mail,
            value: contact.email,
            href: `mailto:${contact.email}`,
            actionLabel: 'Kirim Email',
        });
    }

    if (contact.officeHours) {
        items.push({
            key: 'office-hours',
            label: 'Jam Layanan',
            icon: Clock,
            value: contact.officeHours,
        });
    }

    if (items.length === 0) {
        return null;
    }

    return (
        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {items.map((item) => {
                const Icon = item.icon;

                return (
                    <ParishCard key={item.key}>
                        <ParishCardContent className="flex h-full flex-col gap-3 p-6">
                            <div className="flex items-center gap-2">
                                <Icon
                                    className="size-4 text-red-on-surface"
                                    aria-hidden="true"
                                />
                                <Text
                                    as="h3"
                                    variant="caption-strong"
                                    color="foreground"
                                >
                                    {item.label}
                                </Text>
                            </div>

                            <Text
                                as="p"
                                variant="body"
                                color="muted"
                                className="break-words whitespace-pre-line"
                            >
                                {item.value}
                            </Text>

                            {(item.href || item.copy) && (
                                <div className="mt-auto flex flex-wrap items-center gap-2 pt-2">
                                    {item.href && item.actionLabel ? (
                                        <Button asChild size="sm">
                                            <a
                                                href={item.href}
                                                {...(item.external
                                                    ? {
                                                          target: '_blank',
                                                          rel: 'noopener noreferrer',
                                                      }
                                                    : {})}
                                            >
                                                <Icon aria-hidden="true" />
                                                {item.actionLabel}
                                            </a>
                                        </Button>
                                    ) : null}

                                    {item.copy ? (
                                        <CopyButton
                                            value={item.copy.value}
                                            label={item.copy.label}
                                            className="min-h-11"
                                        />
                                    ) : null}
                                </div>
                            )}
                        </ParishCardContent>
                    </ParishCard>
                );
            })}
        </div>
    );
}

export default ContactDetails;
