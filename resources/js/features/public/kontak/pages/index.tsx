import { usePage } from '@inertiajs/react';
import { Mail, MapPin } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { EmptyState } from '@/components/empty-state';
import { PublicSection } from '@/components/public-section';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import type { SharedProps } from '@/types';
import { ContactDetails } from '../components/contact-details';
import { ContactMap } from '@/components/contact-map';
import { ContactSocial } from '../components/contact-social';

/*
 * /kontak — PRD 7.9.
 *
 * The data is the `contact` shared prop, resolved server-side from
 * SiteSettingsService. Keeping the page a `Route::inertia` rather than adding a
 * controller is deliberate: the footer needs the same contract on every public
 * page, so a shared prop already exists and a second copy for this page would
 * be duplication (D-67).
 *
 * Every section is conditional. When nothing at all is configured the page
 * still renders its heading and an honest message, rather than inventing an
 * address or drawing empty buttons (KON-10).
 */
export default function Kontak() {
    const { contact } = usePage<SharedProps>().props;

    const hasDetails = Boolean(
        contact.address ||
        contact.phone ||
        contact.whatsapp ||
        contact.email ||
        contact.officeHours,
    );

    const hasLocation = Boolean(contact.mapsEmbedUrl || contact.mapsLink);

    const hasSocial = Object.values(contact.social).some((url) => url !== null);

    const isEmpty = !hasDetails && !hasLocation && !hasSocial;

    return (
        <>
            <Seo
                title="Hubungi Kami"
                description="Alamat, telepon, WhatsApp, surel, jam layanan, dan peta lokasi Paroki HSPMTB Putussibau."
            />

            <PublicSection>
                <div className="space-y-8">
                    <SectionHeading
                        eyebrow="Kontak"
                        title="Hubungi Kami"
                        description="Gunakan kanal di bawah ini untuk menghubungi sekretariat paroki. Tidak semua kanal tersedia; hanya yang sudah diisi pengelola situs yang ditampilkan."
                    />

                    {hasDetails ? <ContactDetails contact={contact} /> : null}

                    {isEmpty ? (
                        <EmptyState
                            level={3}
                            icon={Mail}
                            title="Informasi kontak belum tersedia"
                            description="Pengelola situs belum mengisi alamat, telepon, surel, jam layanan, peta, atau media sosial paroki."
                        />
                    ) : null}
                </div>
            </PublicSection>

            {hasLocation ? (
                <PublicSection surface="soft">
                    <div className="space-y-6">
                        <SectionHeading
                            level={3}
                            title="Lokasi"
                            description="Peta dan petunjuk arah hanya ditampilkan bila tautannya valid."
                        />

                        {contact.mapsLink ? (
                            <Button asChild>
                                <a
                                    href={contact.mapsLink}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    <MapPin aria-hidden="true" />
                                    Petunjuk Arah
                                </a>
                            </Button>
                        ) : null}

                        <ContactMap
                            embedUrl={contact.mapsEmbedUrl}
                            directionsUrl={contact.mapsLink}
                        />
                    </div>
                </PublicSection>
            ) : null}

            {hasSocial ? (
                <PublicSection>
                    <div className="space-y-6">
                        <SectionHeading
                            level={3}
                            title="Media Sosial"
                            description="Ikuti kanal resmi paroki yang tersedia."
                        />

                        <ContactSocial social={contact.social} />
                    </div>
                </PublicSection>
            ) : null}
        </>
    );
}
