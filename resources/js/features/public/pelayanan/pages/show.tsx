import { Head } from '@inertiajs/react';
import { CheckCircle2, Image as ImageIcon, Printer } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { DocumentCard } from '@/components/document-card';
import { PublicSection } from '@/components/public-section';
import { RichText } from '@/components/rich-text';
import { SectionHeading } from '@/components/section-heading';
import { Seo } from '@/components/seo';
import { Text } from '@/components/text';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatWibDate } from '@/lib/datetime';
import { home } from '@/routes';
import { index as pelayananIndex } from '@/routes/pelayanan';
import { PublicServiceCard } from '../components/service-card';
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

const FLOW_STEPS = [
    {
        title: 'Baca syarat',
        description: 'Pahami syarat dan persiapan layanan.',
    },
    {
        title: 'Siapkan dokumen',
        description: 'Lengkapi berkas yang dibutuhkan.',
    },
    {
        title: 'Isi Google Form',
        description: 'Daftar melalui tautan pendaftaran yang tersedia.',
    },
    {
        title: 'Tunggu konfirmasi',
        description: 'Sekretariat akan menindaklanjuti pendaftaran Anda.',
    },
];

export default function PelayananShow({ service, otherServices }: Props) {
    const showFlow = !service.is_emergency && service.cta.state === 'form_open';

    return (
        <>
            <Seo
                title={service.seo.title}
                description={service.seo.description}
            />

            <Head title={service.title} />

            <PublicSection>
                <Breadcrumbs
                    breadcrumbs={[
                        { title: 'Beranda', href: home() },
                        { title: 'Pelayanan', href: pelayananIndex() },
                        { title: service.title },
                    ]}
                />

                {service.thumbnail ? (
                    <img
                        src={service.thumbnail}
                        alt={service.thumbnail_alt ?? ''}
                        className="mt-8 aspect-video w-full rounded-xl border border-hairline object-cover"
                    />
                ) : (
                    <div className="mt-8 flex aspect-video w-full items-center justify-center rounded-xl border border-hairline bg-canvas-soft text-muted-foreground">
                        <ImageIcon className="size-10" aria-hidden="true" />
                    </div>
                )}

                <div className="mt-8 grid gap-10 lg:grid-cols-3">
                    <div className="space-y-6 lg:col-span-2">
                        <div className="space-y-2">
                            {service.category ? (
                                <Badge variant="secondary">
                                    {service.category.name}
                                </Badge>
                            ) : null}

                            <Text as="h1" variant="display-lg">
                                {service.title}
                            </Text>

                            {service.summary ? (
                                <Text
                                    as="p"
                                    variant="lead"
                                    color="muted"
                                    className="max-w-2xl"
                                >
                                    {service.summary}
                                </Text>
                            ) : null}
                        </div>

                        <ServiceCta
                            cta={service.cta}
                            contact={service.contact}
                            labels={CTA_LABELS}
                        />

                        {service.introduction ? (
                            <RichText
                                html={service.introduction}
                                className="max-w-3xl"
                            />
                        ) : null}
                    </div>

                    <aside className="space-y-4 rounded-lg border border-hairline bg-canvas-soft p-6">
                        <Text as="h2" variant="h3">
                            Kontak & Pendaftaran
                        </Text>

                        <ServiceCta
                            cta={service.cta}
                            contact={service.contact}
                            labels={CTA_LABELS}
                        />

                        {service.contact.name ? (
                            <Text as="p" variant="caption" color="muted">
                                Penanggung jawab: {service.contact.name}
                            </Text>
                        ) : null}
                    </aside>
                </div>
            </PublicSection>

            {showFlow ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Alur Pendaftaran" />
                    <ol className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                        {FLOW_STEPS.map((step, index) => (
                            <li
                                key={step.title}
                                className="rounded-lg border border-hairline bg-card p-5"
                            >
                                <span
                                    aria-hidden="true"
                                    className="flex size-8 items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground"
                                >
                                    {index + 1}
                                </span>
                                <Text
                                    as="p"
                                    variant="body-strong"
                                    className="mt-3"
                                >
                                    {step.title}
                                </Text>
                                <Text as="p" variant="caption" color="muted">
                                    {step.description}
                                </Text>
                            </li>
                        ))}
                    </ol>
                </PublicSection>
            ) : null}

            {service.requirements.length > 0 ? (
                <PublicSection>
                    <SectionHeading title="Syarat Dokumen" />
                    <ul className="mt-8 space-y-3">
                        {service.requirements.map((requirement) => (
                            <li
                                key={requirement.id}
                                className="flex items-start gap-3"
                            >
                                <CheckCircle2
                                    className="mt-0.5 size-5 shrink-0 text-primary"
                                    aria-hidden="true"
                                />
                                <div>
                                    <Text as="p" variant="body">
                                        {requirement.text}
                                    </Text>
                                    {requirement.note ? (
                                        <Text
                                            as="p"
                                            variant="caption"
                                            color="muted"
                                        >
                                            {requirement.note}
                                        </Text>
                                    ) : null}
                                </div>
                            </li>
                        ))}
                    </ul>
                </PublicSection>
            ) : null}

            {service.steps.length > 0 ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Prosedur Pendaftaran" />
                    <ol className="mt-8 space-y-4">
                        {service.steps.map((step, index) => (
                            <li
                                key={step.id}
                                className="flex items-start gap-4"
                            >
                                <span
                                    aria-hidden="true"
                                    className="flex size-8 shrink-0 items-center justify-center rounded-full bg-navy text-sm font-medium text-white"
                                >
                                    {index + 1}
                                </span>
                                <div>
                                    <Text as="p" variant="body-strong">
                                        {step.title}
                                    </Text>
                                    {step.description ? (
                                        <Text
                                            as="p"
                                            variant="body"
                                            color="muted"
                                        >
                                            {step.description}
                                        </Text>
                                    ) : null}
                                </div>
                            </li>
                        ))}
                    </ol>
                </PublicSection>
            ) : null}

            {service.schedule_info || service.fee_info ? (
                <PublicSection>
                    <div className="grid gap-10 md:grid-cols-2">
                        {service.schedule_info ? (
                            <div>
                                <SectionHeading
                                    title="Jadwal & Persiapan"
                                    level={3}
                                />
                                <Text
                                    as="p"
                                    variant="body"
                                    className="mt-4 whitespace-pre-line"
                                >
                                    {service.schedule_info}
                                </Text>
                            </div>
                        ) : null}

                        {service.fee_info ? (
                            <div>
                                <SectionHeading
                                    title="Biaya/Persembahan"
                                    level={3}
                                />
                                <Text
                                    as="p"
                                    variant="body"
                                    className="mt-4 whitespace-pre-line"
                                >
                                    {service.fee_info}
                                </Text>
                            </div>
                        ) : null}
                    </div>
                </PublicSection>
            ) : null}

            {service.faqs.length > 0 ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Pertanyaan yang Sering Diajukan" />
                    <div className="mt-8">
                        <FaqList items={service.faqs} />
                    </div>
                </PublicSection>
            ) : null}

            {service.documents.length > 0 ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Dokumen Terkait" />
                    <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {service.documents.map((document) => (
                            <DocumentCard
                                key={document.id}
                                document={document}
                            />
                        ))}
                    </div>
                </PublicSection>
            ) : null}

            <PublicSection>
                <div className="flex flex-col items-start gap-6 rounded-lg border border-hairline p-6 md:flex-row md:items-center md:justify-between">
                    <div>
                        <Text as="p" variant="body-strong">
                            Siap mendaftar?
                        </Text>
                        <Text as="p" variant="caption" color="muted">
                            {service.cta.state === 'form_open'
                                ? 'Buka tautan pendaftaran atau hubungi penanggung jawab.'
                                : 'Hubungi kontak yang tersedia untuk informasi lebih lanjut.'}
                        </Text>
                    </div>

                    <ServiceCta
                        cta={service.cta}
                        contact={service.contact}
                        labels={CTA_LABELS}
                    />
                </div>

                {service.updated_at ? (
                    <Text
                        as="p"
                        variant="caption"
                        color="muted"
                        className="mt-6"
                    >
                        Terakhir diperbarui: {formatWibDate(service.updated_at)}
                    </Text>
                ) : null}

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    className="mt-4 print:hidden"
                    onClick={() => window.print()}
                >
                    <Printer className="size-4" aria-hidden="true" />
                    Cetak
                </Button>
            </PublicSection>

            {otherServices.length > 0 ? (
                <PublicSection surface="soft">
                    <SectionHeading title="Layanan Lainnya" />
                    <div className="mt-8 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {otherServices.map((other) => (
                            <PublicServiceCard key={other.id} service={other} />
                        ))}
                    </div>
                </PublicSection>
            ) : null}
        </>
    );
}
