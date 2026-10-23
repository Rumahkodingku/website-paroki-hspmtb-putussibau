import { MessageCircle, Phone } from 'lucide-react';
import { Button } from '@/components/ui/button';
import type { PublicServiceContact } from '../types';

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
            <div className="flex w-full flex-col items-stretch gap-3 sm:w-auto sm:flex-row sm:flex-wrap sm:items-center">
                {contact.tel ? (
                    <Button asChild className="w-full text-sm sm:w-auto">
                        <a href={contact.tel}>
                            <Phone className="size-4" aria-hidden="true" />
                            {labels.call}
                        </a>
                    </Button>
                ) : null}

                {contact.whatsapp ? (
                    <Button
                        asChild
                        variant="outline"
                        className="w-full text-sm sm:w-auto"
                    >
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
        </div>
    );
}
