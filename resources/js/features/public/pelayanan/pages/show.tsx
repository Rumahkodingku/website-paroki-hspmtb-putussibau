import { Head } from '@inertiajs/react';
import {
    ArrowDown,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Image as ImageIcon,
    MessageCircle,
    Users,
} from 'lucide-react';

import { Breadcrumbs } from '@/components/breadcrumbs';
import { DocumentCard } from '@/components/document-card';
import { PublicSection } from '@/components/public-section';
import { RichText } from '@/components/rich-text';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { formatWibDate } from '@/lib/datetime';
import { home } from '@/routes';
import { index as pelayananIndex } from '@/routes/pelayanan';
import { FaqList } from '../components/faq-list';
import { ServiceCta } from '../components/service-cta';

import type {
    PublicServiceCard as PublicServiceCardData,
    PublicServiceDetail,
} from '../types';

type Props = {
    service: PublicServiceDetail;
    otherServices: PublicServiceCardData[];
};

const CTA_LABELS = {
    register_now: 'Daftar Sekarang',
    registration_closed: 'Pendaftaran Ditutup',
    contact_secretariat:
        'Silakan hubungi sekretariat paroki untuk informasi lebih lanjut.',
    contact_now: 'Hubungi Sekarang',
    call: 'Telepon',
    whatsapp: 'WhatsApp',
};

export default function PelayananShow({ service }: Props) {
    const isRegistrationOpen =
        service.cta.state === 'form_open' &&
        !service.is_emergency &&
        Boolean(service.cta.url);

    const hasContact =
        Boolean(service.contact.tel) || Boolean(service.contact.whatsapp);

    return (
        <>
            <Seo
                title={service.seo.title || service.title}
                description={service.seo.description || service.summary}
            />

            <Head title={service.seo.title || service.title} />

            {/* Breadcrumb and editorial hero */}
            <PublicSection>
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        {
                            title: 'Pelayanan',
                            href: pelayananIndex(),
                        },
                        { title: service.title },
                    ]}
                />

                <div className="mt-6 grid items-center gap-8 lg:mt-10 lg:grid-cols-2 lg:gap-12">
                    {/* Hero content */}
                    <div className="order-2 min-w-0 lg:order-1">
                        {service.category ? (
                            <p className="text-xs font-semibold tracking-[0.16em] text-primary-on-surface uppercase sm:text-sm">
                                {service.category.name}
                            </p>
                        ) : null}

                        <Text
                            as="h1"
                            variant="display-lg"
                            className="mt-3 max-w-2xl text-3xl leading-tight font-semibold tracking-tight sm:text-4xl lg:text-5xl"
                        >
                            {service.title}
                        </Text>

                        {service.summary ? (
                            <Text
                                as="p"
                                variant="body-lg"
                                color="muted"
                                className="mt-4 max-w-xl text-base leading-relaxed sm:text-lg"
                            >
                                {service.summary}
                            </Text>
                        ) : null}

                        {service.is_emergency ? (
                            <div className="mt-4 inline-flex items-center gap-2 rounded-full border border-destructive/20 bg-destructive/5 px-3 py-1.5 text-sm font-medium text-destructive">
                                <Clock3 className="size-4" aria-hidden="true" />
                                Pelayanan membutuhkan perhatian segera
                            </div>
                        ) : null}

                        <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:flex-wrap">
                            <ServiceCta
                                cta={service.cta}
                                contact={service.contact}
                                labels={CTA_LABELS}
                            />

                            <a
                                href="#requirements"
                                className="inline-flex min-h-11 items-center justify-center gap-2 rounded-full border border-border px-5 py-2.5 text-sm font-medium transition-colors hover:bg-muted focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none"
                            >
                                Lihat Persyaratan
                                <ArrowDown
                                    className="size-4"
                                    aria-hidden="true"
                                />
                            </a>
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

                    {/* Hero image */}
                    <div className="order-1 min-w-0 lg:order-2">
                        <div className="relative aspect-4/3 overflow-hidden rounded-2xl bg-muted sm:aspect-16/10 lg:aspect-5/4">
                            {service.thumbnail ? (
                                <img
                                    src={service.thumbnail}
                                    alt={service.thumbnail_alt ?? ''}
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

                {/* Quick information */}
                {(service.schedule_info ||
                    service.fee_info ||
                    service.contact.name ||
                    service.contact.phone) && (
                    <div className="mt-8 grid overflow-hidden rounded-2xl border border-border/70 bg-muted/30 sm:mt-10 md:grid-cols-2 lg:grid-cols-3">
                        {service.schedule_info ? (
                            <div className="flex items-start gap-4 p-5 sm:p-6">
                                <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-background text-primary-on-surface">
                                    <CalendarDays
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </div>

                                <div className="min-w-0">
                                    <Text as="h2" variant="heading-sm">
                                        Jadwal Pelaksanaan
                                    </Text>

                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                        className="mt-1 leading-relaxed"
                                    >
                                        {service.schedule_info}
                                    </Text>
                                </div>
                            </div>
                        ) : null}

                        {service.fee_info ? (
                            <div className="flex items-start gap-4 border-t border-border/70 p-5 sm:p-6 md:border-t-0 md:border-l">
                                <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-background text-primary-on-surface">
                                    <CheckCircle2
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </div>

                                <div className="min-w-0">
                                    <Text as="h2" variant="heading-sm">
                                        Biaya / Persembahan
                                    </Text>

                                    <Text
                                        as="p"
                                        variant="body-sm"
                                        color="muted"
                                        className="mt-1 leading-relaxed"
                                    >
                                        {service.fee_info}
                                    </Text>
                                </div>
                            </div>
                        ) : null}

                        {service.contact.name || service.contact.phone ? (
                            <div className="flex items-start gap-4 border-t border-border/70 p-5 sm:p-6 lg:border-t-0 lg:border-l">
                                <div className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-background text-primary-on-surface">
                                    <Users
                                        className="size-5"
                                        aria-hidden="true"
                                    />
                                </div>

                                <div className="min-w-0">
                                    <Text as="h2" variant="heading-sm">
                                        Kontak Sekretariat
                                    </Text>

                                    {service.contact.name ? (
                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                            className="mt-1"
                                        >
                                            {service.contact.name}
                                        </Text>
                                    ) : null}

                                    {service.contact.phone ? (
                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            className="mt-1 font-medium"
                                        >
                                            {service.contact.phone}
                                        </Text>
                                    ) : null}

                                    {service.contact.whatsapp ? (
                                        <a
                                            href={service.contact.whatsapp}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="mt-3 inline-flex min-h-9 items-center gap-2 rounded-full border border-emerald-600/30 bg-background px-3 py-1.5 text-sm font-medium text-emerald-700 transition-colors hover:bg-emerald-50 focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none dark:text-emerald-400 dark:hover:bg-emerald-950/30"
                                        >
                                            <MessageCircle
                                                className="size-4"
                                                aria-hidden="true"
                                            />
                                            Chat via WhatsApp
                                        </a>
                                    ) : null}
                                </div>
                            </div>
                        ) : null}
                    </div>
                )}
            </PublicSection>

            {/* Sacrament introduction */}
            {service.introduction ? (
                <PublicSection surface="soft">
                    <div className="mx-auto max-w-3xl">
                        <p className="text-xs font-semibold tracking-[0.16em] text-primary-on-surface uppercase">
                            Tentang Sakramen
                        </p>

                        <SectionHeading
                            title={`Makna ${service.title}`}
                            level={2}
                        />

                        <RichText
                            html={service.introduction}
                            className="prose-p:leading-8 mt-5 max-w-none"
                        />
                    </div>
                </PublicSection>
            ) : null}

            {/* Requirements and registration timeline */}
            {(service.requirements.length > 0 || service.steps.length > 0) && (
                <PublicSection>
                    <div className="grid items-start gap-8 lg:grid-cols-2 lg:gap-10">
                        {/* Requirements */}
                        {service.requirements.length > 0 ? (
                            <section
                                id="requirements"
                                aria-labelledby="requirements-heading"
                                className="scroll-mt-24 border-r p-5 sm:p-7 lg:p-8"
                            >
                                <SectionHeading
                                    title="Syarat Dokumen"
                                    level={2}
                                />

                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="mt-2"
                                >
                                    Persiapkan dokumen berikut sesuai ketentuan
                                    paroki.
                                </Text>

                                <ul className="mt-6 space-y-5">
                                    {service.requirements.map(
                                        (requirement, index) => (
                                            <li
                                                key={requirement.id}
                                                className="flex items-start gap-3"
                                            >
                                                <span
                                                    className="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary-on-surface text-xs font-semibold text-white"
                                                    aria-hidden="true"
                                                >
                                                    {String(index + 1).padStart(
                                                        2,
                                                        '0',
                                                    )}
                                                </span>

                                                <div className="min-w-0 pt-0.5">
                                                    <Text
                                                        as="p"
                                                        variant="body"
                                                        className="leading-relaxed"
                                                    >
                                                        {requirement.text}
                                                    </Text>

                                                    {requirement.note ? (
                                                        <Text
                                                            as="p"
                                                            variant="body-sm"
                                                            color="muted"
                                                            className="mt-1.5 leading-relaxed"
                                                        >
                                                            {requirement.note}
                                                        </Text>
                                                    ) : null}
                                                </div>
                                            </li>
                                        ),
                                    )}
                                </ul>
                            </section>
                        ) : null}

                        {/* Registration timeline */}
                        {service.steps.length > 0 ? (
                            <section
                                aria-labelledby="steps-heading"
                                className="p-5 sm:p-7 lg:p-8"
                            >
                                <SectionHeading
                                    title="Alur Pendaftaran"
                                    level={2}
                                />

                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="mt-2"
                                >
                                    Ikuti tahapan berikut untuk mempersiapkan{' '}
                                    {service.title}.
                                </Text>

                                <ol className="mt-6 space-y-6">
                                    {service.steps.map((step, index) => (
                                        <li
                                            key={step.id}
                                            className="relative flex items-start gap-4"
                                        >
                                            {index <
                                            service.steps.length - 1 ? (
                                                <span
                                                    aria-hidden="true"
                                                    className="absolute top-9 -bottom-6 left-4 w-px bg-border"
                                                />
                                            ) : null}

                                            <span
                                                className="relative z-10 flex size-8 shrink-0 items-center justify-center rounded-full bg-primary-on-surface text-xs font-semibold text-white"
                                                aria-hidden="true"
                                            >
                                                {String(index + 1).padStart(
                                                    2,
                                                    '0',
                                                )}
                                            </span>

                                            <div className="min-w-0 pt-0.5">
                                                <Text
                                                    as="h3"
                                                    variant="heading-sm"
                                                    className="leading-snug"
                                                >
                                                    {step.title}
                                                </Text>

                                                {step.description ? (
                                                    <Text
                                                        as="p"
                                                        variant="body-sm"
                                                        color="muted"
                                                        className="mt-1.5 leading-relaxed"
                                                    >
                                                        {step.description}
                                                    </Text>
                                                ) : null}
                                            </div>
                                        </li>
                                    ))}
                                </ol>
                            </section>
                        ) : null}
                    </div>
                </PublicSection>
            )}

            {/* Schedule and fee details */}
            {service.schedule_info || service.fee_info ? (
                <PublicSection surface="soft">
                    <div className="mx-auto max-w-3xl">
                        <SectionHeading title="Jadwal & Persiapan" level={2} />

                        {service.schedule_info ? (
                            <Text
                                as="p"
                                variant="body"
                                color="muted"
                                className="mt-4 leading-relaxed whitespace-pre-line"
                            >
                                {service.schedule_info}
                            </Text>
                        ) : null}

                        {service.fee_info ? (
                            <div className="mt-6 border-t border-border/70 pt-5">
                                <Text as="h3" variant="heading-sm">
                                    Biaya / Persembahan
                                </Text>

                                <Text
                                    as="p"
                                    variant="body"
                                    color="muted"
                                    className="mt-2 leading-relaxed whitespace-pre-line"
                                >
                                    {service.fee_info}
                                </Text>
                            </div>
                        ) : null}

                        {hasContact ? (
                            <div className="mt-6">
                                <ServiceCta
                                    cta={{
                                        ...service.cta,
                                        url: '',
                                        label: 'Hubungi Sekretariat',
                                    }}
                                    contact={service.contact}
                                    labels={CTA_LABELS}
                                />
                            </div>
                        ) : null}
                    </div>
                </PublicSection>
            ) : null}

            {/* FAQ */}
            {service.faqs.length > 0 ? (
                <PublicSection>
                    <div className="mx-auto max-w-3xl">
                        <SectionHeading
                            title="Pertanyaan yang Sering Diajukan"
                            level={2}
                        />

                        <div className="mt-6 sm:mt-8">
                            <FaqList items={service.faqs} />
                        </div>
                    </div>
                </PublicSection>
            ) : null}

            {/* Related documents */}
            {service.documents.length > 0 ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Dokumen Terkait" />

                    <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {service.documents.map((document) => (
                            <DocumentCard
                                key={document.id}
                                document={document}
                            />
                        ))}
                    </div>
                </PublicSection>
            ) : null}

            {/* Final CTA */}
            <PublicSection>
                <div className="overflow-hidden rounded-2xl border border-border/70 bg-muted/30 p-6 sm:p-8 lg:p-10">
                    <div className="flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                        <div className="max-w-xl">
                            <Text as="p" variant="heading-sm" color="muted">
                                Mulai Persiapan
                            </Text>

                            <Text
                                as="h2"
                                variant="display-md"
                                className="mt-2 text-2xl leading-tight font-semibold sm:text-3xl"
                            >
                                Siap mempersiapkan {service.title}?
                            </Text>

                            <Text
                                as="p"
                                variant="body"
                                color="muted"
                                className="mt-3 leading-relaxed"
                            >
                                {service.cta.state === 'form_open'
                                    ? 'Mulai pendaftaran melalui tautan yang tersedia atau hubungi sekretariat paroki jika membutuhkan bantuan.'
                                    : 'Hubungi sekretariat paroki untuk memperoleh informasi terbaru mengenai persyaratan dan jadwal pelayanan.'}
                            </Text>

                            {service.contact.name ? (
                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="mt-3"
                                >
                                    Kontak: {service.contact.name}
                                </Text>
                            ) : null}
                        </div>

                        <div className="flex shrink-0 flex-col items-stretch gap-3 sm:flex-row sm:flex-wrap md:flex-col lg:flex-row">
                            <ServiceCta
                                cta={service.cta}
                                contact={service.contact}
                                labels={CTA_LABELS}
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
                            Terakhir diperbarui:{' '}
                            {formatWibDate(service.updated_at)}
                        </Text>
                    ) : null}
                </div>
            </PublicSection>

            {/* Mobile registration bar */}
            {isRegistrationOpen ? (
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
                                Pendaftaran tersedia
                            </Text>
                        </div>

                        <ServiceCta
                            cta={service.cta}
                            contact={{
                                ...service.contact,
                                tel: null,
                                whatsapp: null,
                            }}
                            labels={CTA_LABELS}
                        />
                    </div>
                </div>
            ) : null}
        </>
    );
}
