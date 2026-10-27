import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { Text } from '@/components/text';
import { Button } from '@/components/ui/button';
import { formatWibDateTime } from '@/lib/datetime';
import { dashboard } from '@/routes';
import { index as auditLogsIndex } from '@/routes/audit-logs';
import { AuditDiff } from '../components/audit-diff';
import { AuditResultBadge } from '../components/audit-result-badge';
import type { AuditLogDetailProps } from '../types';

function Field({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="space-y-1">
            <Text
                as="dt"
                variant="fine-print"
                color="muted"
                transform="uppercase"
            >
                {label}
            </Text>
            <Text as="dd" variant="body-sm">
                {value === null || value.trim() === '' ? '—' : value}
            </Text>
        </div>
    );
}

function JsonSection({
    title,
    value,
}: {
    title: string;
    value: Record<string, unknown> | null;
}) {
    if (value === null || Object.keys(value).length === 0) {
        return null;
    }

    return (
        <details className="rounded-md border p-4">
            <summary className="cursor-pointer font-medium">{title}</summary>
            <pre className="mt-3 max-h-96 overflow-auto rounded-md bg-muted/40 p-3 text-xs break-words whitespace-pre-wrap">
                {JSON.stringify(value, null, 2)}
            </pre>
        </details>
    );
}

export default function Show({ event }: AuditLogDetailProps) {
    const { displayTimezone } = usePage().props;

    return (
        <>
            <Head title={`Audit Log #${event.id}`} />

            <div className="space-y-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Button
                            asChild
                            variant="ghost"
                            size="sm"
                            className="-ml-3"
                        >
                            <Link href={auditLogsIndex()}>
                                <ArrowLeft
                                    className="size-4"
                                    aria-hidden="true"
                                />
                                Kembali ke Audit Log
                            </Link>
                        </Button>

                        <div className="flex flex-wrap items-center gap-3">
                            <Text as="h2" variant="h2">
                                Audit Log #{event.id}
                            </Text>
                            <AuditResultBadge
                                result={event.result}
                                label={event.result_label}
                            />
                        </div>

                        <Text as="p" variant="body-sm" color="muted">
                            {event.description}
                        </Text>
                    </div>
                </div>

                <dl className="grid gap-4 rounded-md border p-4 sm:grid-cols-2 lg:grid-cols-3">
                    <Field
                        label="Waktu"
                        value={formatWibDateTime(
                            event.occurred_at,
                            displayTimezone,
                        )}
                    />
                    <Field label="Pengguna" value={event.actor.label} />
                    <Field label="Email pengguna" value={event.actor.email} />
                    <Field label="Aktivitas" value={event.action_label} />
                    <Field label="Modul" value={event.module_label} />
                    <Field label="Objek" value={event.subject_label} />
                    <Field label="Tipe objek" value={event.subject_type} />
                    <Field label="ID objek" value={event.subject_id} />
                    <Field label="Request ID" value={event.request_id} />
                </dl>

                <section className="space-y-3">
                    <Text as="h3" variant="heading-sm">
                        Perubahan
                    </Text>
                    <AuditDiff before={event.before} after={event.after} />
                </section>

                <section className="space-y-3">
                    <Text as="h3" variant="heading-sm">
                        Metadata
                    </Text>
                    <JsonSection
                        title="Metadata event"
                        value={event.metadata}
                    />
                    <JsonSection
                        title="Metadata teknis"
                        value={
                            event.ip_address === null &&
                            event.user_agent === null
                                ? null
                                : {
                                      ip_address: event.ip_address,
                                      user_agent: event.user_agent,
                                  }
                        }
                    />
                </section>
            </div>
        </>
    );
}

Show.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Audit Log', href: auditLogsIndex() },
    ],
};
