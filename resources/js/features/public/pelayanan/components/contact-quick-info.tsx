import { MessageCircle, Phone } from 'lucide-react';
import { Text } from '@/components/text';
import type { PublicServiceDetail } from '../types';

export function ContactQuickInfo({
    service,
}: {
    service: PublicServiceDetail;
}) {
    const contact = service.contact;

    if (!contact.name && !contact.phone && !contact.tel && !contact.whatsapp) {
        return null;
    }

    return (
        <div className="flex h-full items-start gap-3 p-4 sm:p-5">
            <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/5 text-primary">
                <MessageCircle className="size-5" aria-hidden="true" />
            </div>

            <div className="min-w-0 flex-1">
                <Text as="h2" variant="heading-sm">
                    Kontak Sekretariat
                </Text>

                {contact.name ? (
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="mt-1"
                    >
                        {contact.name}
                    </Text>
                ) : null}

                <div className="mt-3 flex flex-wrap gap-2">
                    {contact.tel ? (
                        <a
                            href={contact.tel}
                            className="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-full border border-border px-3 py-1.5 text-xs font-medium transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                        >
                            <Phone className="size-3.5" aria-hidden="true" />
                            Telepon
                        </a>
                    ) : null}

                    {contact.whatsapp ? (
                        <a
                            href={contact.whatsapp}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-full border border-emerald-600/30 px-3 py-1.5 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none dark:text-emerald-400 dark:hover:bg-emerald-950/30"
                        >
                            <MessageCircle
                                className="size-3.5"
                                aria-hidden="true"
                            />
                            WhatsApp
                        </a>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
