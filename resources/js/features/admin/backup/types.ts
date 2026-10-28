import type { Paginated } from '@/types';

export type BackupType = 'database' | 'full';
export type BackupSource = 'manual' | 'scheduled' | 'uploaded' | 'pre_restore';
export type BackupStatus =
    'queued' | 'running' | 'validating' | 'success' | 'failed';
export type BackupValidationStatus = 'pending' | 'passed' | 'failed';
export type RestoreStatus =
    | 'queued'
    | 'preparing'
    | 'ready'
    | 'running'
    | 'success'
    | 'failed'
    | 'partial'
    | 'expired';

export type BackupRestoreRow = {
    id: number;
    uuid: string;
    status: RestoreStatus;
    status_label: string;
    database_result: string | null;
    database_result_label: string | null;
    files_result: string | null;
    files_result_label: string | null;
    safety_backup_id: number | null;
    safety_backup_status: string | null;
    requested_by_label: string | null;
    created_at: string | null;
    confirmed_at: string | null;
    expires_at: string | null;
    started_at: string | null;
    finished_at: string | null;
    duration_ms: number | null;
    failure_code: string | null;
    failure_message: string | null;
    verification: Record<string, boolean> | null;
    can_confirm: boolean;
};

export type BackupRow = {
    id: number;
    uuid: string;
    type: BackupType;
    type_label: string;
    source: BackupSource;
    source_label: string;
    status: BackupStatus;
    status_label: string;
    validation_status: BackupValidationStatus;
    validation_label: string;
    file_size: number | null;
    duration_ms: number | null;
    created_at: string | null;
    validated_at: string | null;
    created_by_label: string | null;
    scheduled_slot: string | null;
    is_available: boolean;
    file_missing_at: string | null;
    can: {
        download: boolean;
        restore: boolean;
        delete: boolean;
    };
};

export type BackupDetail = BackupRow & {
    original_filename: string | null;
    checksum: string | null;
    format_version: string | null;
    note: string | null;
    failure_code: string | null;
    failure_message: string | null;
    started_at: string | null;
    finished_at: string | null;
    protected_until: string | null;
    restores: BackupRestoreRow[];
};

export type BackupSummary = {
    total: number;
    last_backup: BackupRow | null;
    last_success: BackupRow | null;
    active_restore: {
        id: number;
        uuid: string;
        status: RestoreStatus;
        status_label: string;
        backup_id: number;
    } | null;
};

export type Heartbeat = {
    ready: boolean;
    last_seen_at: string | null;
};

export type Readiness = {
    scheduler: Heartbeat;
    worker: Heartbeat;
    queue_ready: boolean;
    binary: {
        mysqldump: boolean;
        mysql: boolean;
        ready: boolean;
    };
    storage: {
        local: boolean;
        free_bytes: number | null;
        low: boolean;
    };
    upload_limit: {
        upload_max_filesize: number;
        post_max_size: number;
        effective: number;
    };
};

export type BackupFilters = {
    search: string;
    type: string;
    source: string;
    status: string;
    validasi: string;
    dari: string;
    sampai: string;
    sort: string;
    direction: string;
};

export type BackupOption = {
    value: string;
    label: string;
};

export type BackupIndexProps = {
    backups: Paginated<BackupRow>;
    filters: BackupFilters;
    summary: BackupSummary;
    readiness: Readiness;
};

export type BackupShowProps = {
    backup: BackupDetail;
    readiness: Readiness;
};

export type BackupSettings = {
    schedule_enabled: boolean;
    database_enabled: boolean;
    full_enabled: boolean;
    database_time: string;
    full_day: number;
    full_time: string;
    timezone: string;
    retention_daily_days: number;
    retention_weekly_weeks: number;
    retention_invalid_upload_days: number;
    retention_safety_days: number;
    max_upload_megabytes: number;
    max_expanded_megabytes: number;
    max_files: number;
    max_compression_ratio: number;
    low_disk_threshold_megabytes: number;
};

export type BackupSettingsPage = {
    settings: BackupSettings;
    timezones: string[];
    readiness: Readiness;
};

export type BackupSettingsProps = {
    settingsPage: BackupSettingsPage;
};

export type BackupFormData = {
    type: BackupType;
    note: string;
};

export type UploadBackupFormData = {
    file: File | null;
};

export type RestoreFormData = {
    restore_id: number | '';
    confirmation: string;
};
