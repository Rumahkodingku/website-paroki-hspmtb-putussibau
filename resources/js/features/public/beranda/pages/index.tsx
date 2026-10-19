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

/**
 * The public homepage.
 *
 * The page is deliberately a composition, not a data owner. The controller
 * decides what each block may see; this file decides how the blocks sit next to
 * each other. Every section that has no data returns null on its own, so the
 * page never leaves a heading above an empty region.
 *
 * The layout alternates surfaces and shapes so the page does not read as one
 * card grid repeated: full-bleed photograph, divided quick links, a soft mass
 * band, editorial news cards, a two-column agenda, a navy reflection, a list of
 * services, a photographic strip, then the contact block.
 */
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
