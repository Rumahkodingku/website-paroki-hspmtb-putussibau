import { Head } from '@inertiajs/react';
import {
    ArrowDown,
    Clock3,
    ClipboardList,
    Image as ImageIcon,
    Users,
} from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { PublicSection } from '@/components/public-section';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { home } from '@/routes';
import { index as pelayananIndex } from '@/routes/pelayanan';
import { ServiceCta } from '../service-cta';
import { ContactQuickInfo } from '../contact-quick-info';
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

type Props = {
    service: PublicServiceDetail;
};

export function HeroSection({ service }: Props) {
    const hasContact =
        Boolean(service.contact.tel) || Boolean(service.contact.whatsapp);
    const hasContactInfo =
        Boolean(service.contact.name) ||
        Boolean(service.contact.phone) ||
        hasContact;

    return (
        <>
            <Seo
                title={service.seo.title || service.title}
                description={service.seo.description || service.summary}
            />
            <Head title={service.seo.title || service.title} />

            <PublicSection className="py-7 sm:py-9 lg:py-12">
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Pelayanan', href: pelayananIndex() },
                        { title: service.title },
                    ]}
                />

                <div className="mt-5 grid items-center gap-6 sm:mt-7 sm:gap-8 lg:mt-9 lg:grid-cols-[0.9fr_1.1fr] lg:gap-12">
                    <div className="order-2 min-w-0 lg:order-1">
                        {service.category ? (
                            <p className="text-xs font-semibold tracking-[0.16em] text-primary uppercase sm:text-sm">
                                {service.category.name}
                            </p>
                        ) : null}

                        <Text
                            as="h1"
                            variant="display-lg"
                            className="mt-2 text-3xl leading-tight font-semibold tracking-tight sm:text-4xl lg:text-5xl"
                        >
                            {service.title}
                        </Text>

                        {service.summary ? (
                            <Text
                                as="p"
                                variant="body-lg"
                                color="muted"
                                className="mt-3 max-w-xl text-sm leading-relaxed sm:mt-4 sm:text-base lg:text-lg"
                            >
                                {service.summary}
                            </Text>
                        ) : null}

                        {service.is_emergency ? (
                            <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-destructive/20 bg-destructive/5 px-3 py-1.5 text-sm font-medium text-destructive">
                                <Clock3 className="size-4" aria-hidden="true" />
                                Pelayanan darurat
                            </div>
                        ) : null}

                        <div className="mt-5 flex flex-col gap-2.5 sm:flex-row sm:flex-wrap sm:items-center">
                            <ServiceCta
                                cta={service.cta}
                                contact={service.contact}
                                labels={CTA_LABELS}
                                className="w-full sm:w-auto [&>a]:w-full sm:[&>a]:w-auto"
                            />

                            {service.requirements.length > 0 ? (
                                <a
                                    href="#requirements"
                                    className="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full border border-border px-5 py-2.5 text-sm font-semibold transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none sm:w-auto"
                                >
                                    Lihat Persyaratan
                                    <ArrowDown
                                        className="size-4"
                                        aria-hidden="true"
                                    />
                                </a>
                            ) : null}
                        </div>

                        {service.cta.message ? (
                            <Text
                                as="p"
                                variant="body-sm"
                                color="muted"
                                className="mt-3"
                            >
                                {service.cta.message}
                            </Text>
                        ) : null}
                    </div>

                    <div className="order-1 min-w-0 lg:order-2">
                        <div className="relative aspect-4/3 overflow-hidden rounded-4xl bg-muted sm:aspect-16/10 lg:aspect-5/4">
                            {service.thumbnail ? (
                                <img
                                    src={service.thumbnail}
                                    alt={
                                        service.thumbnail_alt?.trim() ||
                                        service.title
                                    }
                                    fetchPriority="high"
                                    decoding="async"
                                    className="size-full object-cover"
                                />
                            ) : (
                                <div className="flex size-full items-center justify-center text-muted-foreground">
                                    <ImageIcon
                                        className="size-12"
                                        aria-hidden="true"
                                    />
                                </div>
                            )}
                        </div>
                    </div>
                </div>

                {service.audience_info ||
                service.preparation_info ||
                hasContactInfo ? (
                    <div className="mt-6 grid gap-3 sm:mt-8 sm:grid-cols-2 xl:grid-cols-3">
                        {service.audience_info ? (
                            <div className="flex h-full items-start gap-3 p-4 sm:p-5">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-background text-primary">
                                    <Users
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div className="min-w-0">
                                    <Text as="h2" variant="heading-sm">
                                        Sasaran Pelayanan
                                    </Text>
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                        className="mt-1.5 leading-relaxed whitespace-pre-line"
                                    >
                                        {service.audience_info}
                                    </Text>
                                </div>
                            </div>
                        ) : null}

                        {service.preparation_info ? (
                            <div className="flex h-full items-start gap-3 p-4 sm:p-5">
                                <div className="flex size-10 shrink-0 items-center justify-center rounded-xl bg-background text-primary">
                                    <ClipboardList
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div className="min-w-0">
                                    <Text as="h2" variant="heading-sm">
                                        Persiapan
                                    </Text>
                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                        className="mt-1.5 leading-relaxed whitespace-pre-line"
                                    >
                                        {service.preparation_info}
                                    </Text>
                                </div>
                            </div>
                        ) : null}

                        {hasContactInfo ? (
                            <ContactQuickInfo service={service} />
                        ) : null}
                    </div>
                ) : null}
            </PublicSection>
        </>
    );
}
