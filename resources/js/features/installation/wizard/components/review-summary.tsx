import { ChevronDown } from 'lucide-react';
import { Text } from '@/components/text';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { ReviewSummary as ReviewSummaryData } from '../types';

export function Row({
    label,
    value,
    padding = 'py-2.5',
    valueClassName = 'break-words',
}: {
    label: string;
    value: string | null;
    padding?: string;
    valueClassName?: string;
}) {
    return (
        <div
            className={`grid gap-1 ${padding} sm:grid-cols-[minmax(0,11rem)_minmax(0,1fr)] sm:gap-4`}
        >
            <Text as="dt" variant="body-sm" color="muted">
                {label}
            </Text>
            <Text
                as="dd"
                variant="body-sm"
                weight="medium"
                className={valueClassName}
            >
                {value && value !== '' ? value : '—'}
            </Text>
        </div>
    );
}

function Section({
    title,
    description,
    children,
}: {
    title: string;
    description: string;
    children: React.ReactNode;
}) {
    return (
        <Collapsible defaultOpen={false} className="group">
            <CollapsibleTrigger className="flex w-full items-center justify-between gap-4 py-2 text-left transition-colors hover:text-primary focus-visible:ring-2 focus-visible:ring-primary focus-visible:outline-none">
                <div className="flex flex-col gap-0.5">
                    <Text as="h2" variant="h5">
                        {title}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {description}
                    </Text>
                </div>
                <ChevronDown
                    className="size-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]:rotate-180"
                    aria-hidden="true"
                />
            </CollapsibleTrigger>
            <CollapsibleContent>
                <dl className="divide-y divide-border">{children}</dl>
            </CollapsibleContent>
        </Collapsible>
    );
}

export default function ReviewSummary({
    summary,
}: {
    summary: ReviewSummaryData;
}) {
    const { database, email, admin, application, meta } = summary;

    return (
        <div className="flex flex-col gap-6">
            <Section
                title="Database"
                description="Koneksi database yang akan digunakan."
            >
                <Row label="Driver" value={database.driver} />
                <Row
                    label="Host"
                    value={
                        database.host
                            ? `${database.host}${database.port ? `:${database.port}` : ''}`
                            : null
                    }
                />
                <Row label="Nama database" value={database.database} />
                <Row label="Pengguna" value={database.username} />
            </Section>

            <Section title="Email" description="Konfigurasi pengiriman email.">
                <Row label="Mailer" value={email.mailer} />
                <Row
                    label="Host"
                    value={
                        email.host
                            ? `${email.host}${email.port ? `:${email.port}` : ''}`
                            : null
                    }
                />
                <Row label="Pengirim" value={email.from_address} />
                <Row label="Nama pengirim" value={email.from_name} />
            </Section>

            <Section
                title="Akun"
                description="Akun Super Admin yang akan dibuat."
            >
                <Row label="Nama Lengkap" value={admin.name} />
                <Row label="Email" value={admin.email} />
            </Section>

            <Section
                title="Identitas paroki"
                description="Informasi identitas paroki."
            >
                <Row label="Nama paroki" value={application.parish_name} />
                <Row
                    label="Nama singkat"
                    value={application.parish_short_name}
                />
                <Row label="Tagline" value={application.tagline} />
                <Row
                    label="Deskripsi singkat"
                    value={application.short_description}
                />
                <Row label="Patron santo" value={application.patron_saint} />
            </Section>

            <Section title="Kontak" description="Informasi kontak paroki.">
                <Row label="Alamat" value={application.contact_address} />
                <Row label="Telepon" value={application.contact_phone} />
                <Row label="WhatsApp" value={application.contact_whatsapp} />
                <Row label="Surel" value={application.contact_email} />
                <Row label="Tautan Google Maps" value={application.maps_link} />
            </Section>

            <Section
                title="Media sosial"
                description="Tautan media sosial paroki."
            >
                <Row label="Facebook" value={application.social_facebook} />
                <Row label="Instagram" value={application.social_instagram} />
                <Row label="YouTube" value={application.social_youtube} />
                <Row
                    label="Kanal WhatsApp"
                    value={application.social_whatsapp_channel}
                />
            </Section>

            <Section
                title="Konfigurasi aplikasi"
                description="Pengaturan dasar aplikasi."
            >
                <Row label="Nama aplikasi" value={meta.appName} />
                <Row label="URL aplikasi" value={meta.appUrl} />
                <Row label="Zona waktu" value={meta.timezone} />
                <Row label="Bahasa" value={meta.locale} />
                <Row label="Environment" value={meta.environment} />
            </Section>
        </div>
    );
}
