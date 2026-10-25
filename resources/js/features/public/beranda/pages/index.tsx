import { usePage } from '@inertiajs/react';
import { Seo } from '@/components/seo';
import type { SharedProps } from '@/types';
import { HomeContactSection } from '../components/home-contact-section';
import { HomeAgendaSection } from '../components/home-agenda-section';
import { HomeDevotionSection } from '../components/home-devotion-section';
import { HomeGallerySection } from '../components/home-gallery-section';
import { HomeHero } from '../components/home-hero';
import { HomeNewsSection } from '../components/home-news-section';
import { HomeNextMassSection } from '../components/home-next-mass-section';
import { HomeQuickLinks } from '../components/home-quick-links';
import { HomeServicesSection } from '../components/home-services-section';
import type { HomepageProps } from '../types';

export default function Beranda({
    nextMass,
    news,
    events,
    devotion,
    services,
    galleryPhotos,
    galleryIllustrations,
    hero,
    sections,
}: HomepageProps) {
    const { contact, displayTimezone, identity, seo } =
        usePage<SharedProps>().props;

    return (
        <>
            <Seo
                title={seo.title ?? identity.shortName ?? 'Beranda'}
                description={
                    seo.description ??
                    hero.description ??
                    identity.shortDescription
                }
                ogImage={seo.ogImage ?? hero.ogImage}
                ogType="website"
            />

            <HomeHero hero={hero} identity={identity} />

            <HomeQuickLinks />

            <HomeNextMassSection
                nextMass={nextMass}
                displayTimezone={displayTimezone}
            />

            <HomeNewsSection news={news} displayTimezone={displayTimezone} />

            <HomeAgendaSection
                events={events}
                displayTimezone={displayTimezone}
            />

            <HomeDevotionSection
                devotion={devotion}
                enabled={sections.devotion}
                displayTimezone={displayTimezone}
            />

            <HomeServicesSection
                services={services}
                enabled={sections.services}
            />

            <HomeGallerySection
                galleryPhotos={galleryPhotos}
                galleryIllustrations={galleryIllustrations}
                enabled={sections.gallery}
            />

            <HomeContactSection contact={contact} />
        </>
    );
}
