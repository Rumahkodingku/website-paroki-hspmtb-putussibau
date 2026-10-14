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

/**
 * The registration call to action, one branch per state PRD 7.6 defines.
 *
 * The branch is chosen on the server (PublicServiceDetailResource) and mirrored
 * here, so the page cannot invent a fifth state or show a form button for a
 * service that has none. Buttons are `print:hidden`: a printed sheet cannot open
 * a Google Form, and the address is more useful on paper than an inert button.
 */
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
                <Button asChild size="lg" className="print:hidden">
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
                    className="print:hidden"
                >
                    {labels.registration_closed}
                </Button>

                {cta.message ? (
                    <Text as="p" variant="body" color="muted" className="mt-3">
                        {cta.message}
                    </Text>
                ) : null}
            </div>
        );
    }

    if (cta.state === 'emergency') {
        return (
            <div className={className}>
                <Text as="p" variant="body-strong" className="mb-3">
                    {labels.contact_now}
                </Text>

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
                    className="rounded-lg border border-dashed border-hairline p-4"
                >
                    {labels.contact_secretariat}
                </Text>
            )}
        </div>
    );
}
