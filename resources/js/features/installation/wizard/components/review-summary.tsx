import { Text } from '@/components/text';
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
    children,
}: {
    title: string;
    children: React.ReactNode;
}) {
    return (
        <section className="flex flex-col gap-1">
            <Text as="h2" variant="h5">
                {title}
            </Text>
            <dl className="divide-y divide-border border-y border-border">
                {children}
            </dl>
        </section>
    );
}

export default function ReviewSummary({
    summary,
}: {
    summary: ReviewSummaryData;
}) {
    const { database, admin, application, meta } = summary;

    return (
        <div className="flex flex-col gap-6">
            <Section title="Database">
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

            <Section title="Super Admin">
                <Row label="Nama" value={admin.name} />
                <Row label="Email" value={admin.email} />
            </Section>

            <Section title="Identitas paroki">
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

            <Section title="Konfigurasi aplikasi">
                <Row label="Nama aplikasi" value={meta.appName} />
                <Row label="URL aplikasi" value={meta.appUrl} />
                <Row label="Zona waktu" value={meta.timezone} />
                <Row label="Bahasa" value={meta.locale} />
                <Row label="Environment" value={meta.environment} />
            </Section>
        </div>
    );
}
