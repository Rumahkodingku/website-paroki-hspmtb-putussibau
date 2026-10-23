import { HeroSection } from '../components/sections/hero-section';
import { IntroductionSection } from '../components/sections/introduction-section';
import { RequirementsProcedureSection } from '../components/sections/requirements-procedure-section';
import { ScheduleFeeDesktopSection } from '../components/sections/schedule-fee-desktop-section';
import { FaqSection } from '../components/sections/faq-section';
import { DocumentsSection } from '../components/sections/documents-section';
import { FinalCtaSection } from '../components/sections/final-cta-section';
import { MobileActionBar } from '../components/sections/mobile-action-bar';

import type {
    PublicServiceCard as PublicServiceCardData,
    PublicServiceDetail,
} from '../types';

type Props = {
    service: PublicServiceDetail;
    otherServices: PublicServiceCardData[];
};

export default function PelayananShow({ service }: Props) {
    const isRegistrationOpen =
        service.cta.state === 'form_open' &&
        !service.is_emergency &&
        Boolean(service.cta.url);

    const hasContact =
        Boolean(service.contact.tel) || Boolean(service.contact.whatsapp);

    const showMobileAction =
        isRegistrationOpen || (service.is_emergency && hasContact);

    return (
        <>
            <HeroSection service={service} />

            <IntroductionSection service={service} />

            <RequirementsProcedureSection service={service} />

            <ScheduleFeeDesktopSection service={service} />

            <FaqSection service={service} />

            <DocumentsSection service={service} />

            <FinalCtaSection
                service={service}
                showMobileAction={showMobileAction}
            />

            <MobileActionBar
                service={service}
                isRegistrationOpen={isRegistrationOpen}
                showMobileAction={showMobileAction}
            />
        </>
    );
}
