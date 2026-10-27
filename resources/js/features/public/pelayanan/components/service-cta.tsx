import { ExternalLink } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Text } from '@/components/text';
import { ContactActions } from './contact-actions';
import type { PublicServiceContact, PublicServiceCta as Cta } from '../types';

type Labels = {
    register_now: string;
    registration_closed: string;
    contact_secretariat: string;
    contact_now: string;
    call: string;
    whatsapp: string;
};

export function ServiceCta({
    cta,
    contact,
    labels,
    className,
}: {
    cta: Cta;
    contact: PublicServiceContact;
    labels: Labels;
    className?: string;
}) {
    if (cta.state === 'form_open' && cta.url) {
        return (
            <div className={className}>
                <Button asChild size="lg" className="text-sm print:hidden">
                    <a href={cta.url} target="_blank" rel="noopener noreferrer">
                        <ExternalLink className="size-4" aria-hidden="true" />
                        {cta.label || labels.register_now}
                    </a>
                </Button>
            </div>
        );
    }

    if (cta.state === 'form_closed') {
        return (
            <div className={className}>
                <Button
                    type="button"
                    size="lg"
                    disabled
                    className="text-sm print:hidden"
                >
                    {labels.registration_closed}
                </Button>
            </div>
        );
    }

    if (cta.state === 'emergency') {
        return (
            <div className={className}>
                {contact.tel || contact.whatsapp ? (
                    <ContactActions
                        contact={contact}
                        labels={labels}
                        className="print:hidden"
                    />
                ) : (
                    <Text as="p" variant="body" color="muted">
                        {labels.contact_secretariat}
                    </Text>
                )}
            </div>
        );
    }

    // contact
    return (
        <div className={className}>
            {contact.tel || contact.whatsapp ? (
                <ContactActions
                    contact={contact}
                    labels={labels}
                    className="print:hidden"
                />
            ) : (
                <Text
                    as="p"
                    variant="body"
                    color="muted"
                    className="rounded-lg border border-dashed border-border p-4"
                >
                    {labels.contact_secretariat}
                </Text>
            )}
        </div>
    );
}
