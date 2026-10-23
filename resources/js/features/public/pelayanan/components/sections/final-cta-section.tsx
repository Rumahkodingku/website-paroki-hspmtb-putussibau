import { PublicSection } from '@/components/public-section';
import { Text } from '@/components/text';
import { ServiceCta } from '../service-cta';
import { formatWibDate } from '@/lib/datetime';
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

export function FinalCtaSection({
    service,
    showMobileAction,
}: {
    service: PublicServiceDetail;
    showMobileAction: boolean;
}) {
    return (
        <PublicSection className={showMobileAction ? 'pb-28 sm:pb-12' : ''}>
            <div className="p-5 sm:p-7 lg:p-9">
                <div className="flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                    <div className="max-w-2xl">
                        <p className="text-xs font-semibold tracking-[0.14em] text-primary uppercase">
                            Mulai Persiapan
                        </p>
                        <Text
                            as="h2"
                            variant="display-md"
                            className="mt-2 text-2xl leading-tight sm:text-3xl"
                            weight="semibold"
                        >
                            Siap menerima {service.title}?
                        </Text>
                        <Text
                            as="p"
                            variant="body"
                            color="muted"
                            className="mt-3 leading-relaxed"
                        >
                            {service.cta.state === 'form_open'
                                ? 'Lanjutkan melalui pendaftaran yang tersedia atau hubungi sekretariat paroki jika membutuhkan informasi lebih lanjut.'
                                : 'Hubungi sekretariat paroki melalui kontak yang tersedia untuk mendapatkan informasi dan bantuan terkait layanan ini.'}
                        </Text>
                    </div>

                    <div className="flex shrink-0 flex-col items-stretch gap-3 sm:flex-row md:flex-col lg:flex-row">
                        <ServiceCta
                            cta={service.cta}
                            contact={service.contact}
                            labels={CTA_LABELS}
                            className="w-full sm:w-auto [&>a]:w-full sm:[&>a]:w-auto"
                        />
                    </div>
                </div>

                {service.updated_at ? (
                    <Text
                        as="p"
                        variant="body-sm"
                        color="muted"
                        className="mt-6 border-t border-border/70 pt-4"
                    >
                        Terakhir diperbarui: {formatWibDate(service.updated_at)}
                    </Text>
                ) : null}
            </div>
        </PublicSection>
    );
}
