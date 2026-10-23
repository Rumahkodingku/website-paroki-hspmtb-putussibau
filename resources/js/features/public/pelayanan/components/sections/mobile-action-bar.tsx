import { Text } from '@/components/text';
import { ServiceCta } from '../service-cta';
import type { PublicServiceDetail } from '../../types';

const CTA_LABELS = {
    register_now: 'Daftar Sekarang',
    registration_closed: 'Pendaftaran Ditutup',
    contact_secretariat:
        'Silakan hubungi sekretariat paroki untuk informasi lebih lanjut.',
    contact_now: 'Hubungi Sekarang',
    call: 'Telepon',
    whatsapp: 'WhatsApp',
};

export function MobileActionBar({
    service,
    isRegistrationOpen,
    showMobileAction,
}: {
    service: PublicServiceDetail;
    isRegistrationOpen: boolean;
    showMobileAction: boolean;
}) {
    if (!showMobileAction) return null;

    return (
        <div className="fixed inset-x-0 bottom-0 z-40 border-t border-border bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] backdrop-blur-md sm:hidden print:hidden">
            <div className="mx-auto flex max-w-lg items-center gap-3">
                <div className="min-w-0 flex-1">
                    <Text
                        as="p"
                        variant="body-sm"
                        className="truncate font-semibold"
                    >
                        {service.title}
                    </Text>
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="text-xs"
                    >
                        {isRegistrationOpen
                            ? 'Pendaftaran tersedia'
                            : 'Hubungi sekretariat'}
                    </Text>
                </div>
                <ServiceCta
                    cta={service.cta}
                    contact={service.contact}
                    labels={CTA_LABELS}
                    className="shrink-0 [&_button]:min-h-10 [&_button]:px-4 [&_button]:text-xs [&>a]:min-h-10 [&>a]:px-4 [&>a]:text-xs"
                />
            </div>
        </div>
    );
}
