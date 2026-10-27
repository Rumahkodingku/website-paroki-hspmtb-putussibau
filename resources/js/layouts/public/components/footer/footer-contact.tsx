import { Link } from '@inertiajs/react';
import { Mail, MessageCircle, Phone } from 'lucide-react';
import { Text } from '@/components/text';
import type { ContactDetails } from '@/types';
import { index as kontak } from '@/routes/kontak';
import { FooterSocial } from './footer-social';

type FooterContactProps = {
    contact: ContactDetails | undefined;
};

export function FooterContact({ contact }: FooterContactProps) {
    const rows = [
        contact?.phone
            ? {
                  key: 'phone',
                  Icon: Phone,
                  label: 'Telepon',
                  value: contact.phone.display,
                  href: contact.phone.tel,
              }
            : null,
        contact?.whatsapp
            ? {
                  key: 'whatsapp',
                  Icon: MessageCircle,
                  label: 'WhatsApp',
                  value: contact.whatsapp.display ?? 'WhatsApp',
                  href: contact.whatsapp.url,
                  external: true,
              }
            : null,
        contact?.email
            ? {
                  key: 'email',
                  Icon: Mail,
                  label: 'Surel',
                  value: contact.email,
                  href: `mailto:${contact.email}`,
              }
            : null,
    ].filter((row): row is NonNullable<typeof row> => row !== null);

    return (
        <section aria-label="Hubungi Kami">
            <Text
                as="h2"
                variant="label"
                color="foreground"
                className="relative inline-flex pb-2"
            >
                Hubungi Kami
            </Text>
            {rows.length > 0 ? (
                <ul className="mt-3 mb-4 space-y-2">
                    {rows.map(({ key, Icon, label, value, href, ...rest }) => (
                        <li
                            key={key}
                            className="flex min-w-0 items-start gap-2.5"
                        >
                            <Icon
                                className="mt-0.5 size-4 shrink-0 text-foreground"
                                aria-hidden="true"
                            />
                            <a
                                href={href}
                                aria-label={`${label}: ${value}`}
                                {...('external' in rest && rest.external
                                    ? {
                                          target: '_blank',
                                          rel: 'noopener noreferrer',
                                      }
                                    : {})}
                                className="text-fine-print wrap-break-word text-muted-foreground transition-colors hover:text-primary-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                            >
                                {value}
                            </a>
                        </li>
                    ))}
                </ul>
            ) : null}

            <FooterSocial contact={contact} />
            <ul className="mt-4 space-y-2">
                <li>
                    <Link
                        href={kontak()}
                        className="inline-flex min-h-8 items-center text-fine-print text-muted-foreground transition-colors hover:text-primary-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                    >
                        Halaman kontak
                    </Link>
                </li>
                {contact?.mapsLink ? (
                    <li>
                        <a
                            href={contact.mapsLink}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex min-h-8 items-center text-fine-print text-muted-foreground transition-colors hover:text-primary-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            Petunjuk arah
                        </a>
                    </li>
                ) : null}
            </ul>
        </section>
    );
}
