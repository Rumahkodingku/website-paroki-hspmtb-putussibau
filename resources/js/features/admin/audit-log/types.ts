import type { Paginated } from '@/types';

export type AuditActor = {
    type: 'user' | 'system' | 'anonymous';
    name: string | null;
    label: string;
};

export type AuditLogRow = {
    id: number;
    occurred_at: string;
    actor: AuditActor;
    action: string;
    action_label: string;
    module: string;
    module_label: string;
    subject_label: string | null;
    description: string;
    result: string;
    result_label: string;
};

export type AuditLogDetail = Omit<AuditLogRow, 'actor'> & {
    actor: AuditActor & { email: string | null };
    subject_type: string | null;
    subject_id: string | null;
    before: Record<string, unknown> | null;
    after: Record<string, unknown> | null;
    metadata: Record<string, unknown> | null;
    request_id: string | null;
    ip_address: string | null;
    user_agent: string | null;
};

export type AuditFilters = {
    search: string;
    from: string;
    to: string;
    actor: string;
    module: string;
    action: string;
    result: string;
    sort: string;
    direction: string;
};

export type AuditOption = {
    value: string;
    label: string;
};

export type AuditLogIndexProps = {
    events: Paginated<AuditLogRow>;
    filters: AuditFilters;
    options: {
        modules: AuditOption[];
        actions: AuditOption[];
        results: AuditOption[];
        actors: AuditOption[];
    };
};

export type AuditLogDetailProps = {
    event: AuditLogDetail;
};
