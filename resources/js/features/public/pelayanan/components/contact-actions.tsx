import { MessageCircle, Phone } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/text';
import type { PublicServiceContact } from '../types';

/**
 * The telephone and WhatsApp buttons a contact produces.
 *
 * Renders nothing when the number did not normalize, so a service with no usable
 * contact shows no button rather than a dead `tel:` link (roadmap 9.2.2).
 */
export function ContactActions({
    contact,
    labels,
    className,
}: {
    contact: PublicServiceContact;
    labels: { call: string; whatsapp: string };
    className?: string;
}) {
    if (!contact.tel && !contact.whatsapp) {
        return null;
    }

    return (
        <div className={className}>
            <div className="flex flex-wrap items-center gap-3">
                {contact.tel ? (
                    <Button asChild>
                        <a href={contact.tel}>
                            <Phone className="size-4" aria-hidden="true" />
                            {labels.call}
                        </a>
                    </Button>
                ) : null}

                {contact.whatsapp ? (
                    <Button asChild variant="outline">
                        <a
                            href={contact.whatsapp}
                            target="_blank"
                            rel="noopener noreferrer"
                        >
                            <MessageCircle
                                className="size-4"
                                aria-hidden="true"
                            />
                            {labels.whatsapp}
                        </a>
                    </Button>
                ) : null}
            </div>

            {contact.phone ? (
                <Text as="p" variant="body-sm" color="muted" className="mt-3">
                    {contact.phone}
                </Text>
            ) : null}
        </div>
    );
}
