export type RequirementStatus = 'pass' | 'warning' | 'fail';

export type RequirementCheck = {
    key: string;
    label: string;
    status: RequirementStatus;
    detail: string;
    action: string | null;
};

export type InstallationStatus =
    'pending' | 'installing' | 'failed' | 'completed';

export type ProgressState = {
    status: InstallationStatus;
    current_step: string | null;
    started_at: string | null;
    completed_at: string | null;
    failed_at: string | null;
    error_code: string | null;
};

export type InstallationErrorPayload = {
    code: string;
    message: string;
};

export type DatabaseInfo = {
    driver: string;
    host: string | null;
    port: number | null;
    database: string | null;
    username: string | null;
    supported: boolean;
};

export type ApplicationMeta = {
    appName: string;
    appUrl: string;
    timezone: string;
    locale: string;
    environment: string;
};

export type ApplicationValues = {
    parish_name: string | null;
    parish_short_name: string | null;
    tagline: string | null;
    short_description: string | null;
    patron_saint: string | null;
};

export type EmailValues = {
    mailer: string | null;
    host: string | null;
    port: number | null;
    username: string | null;
    password: string | null;
    from_address: string | null;
    from_name: string | null;
};

export type ReviewSummary = {
    database: DatabaseInfo;
    email: EmailValues;
    admin: { name: string; email: string };
    application: ApplicationValues;
    meta: ApplicationMeta;
};

/**
 * The eight steps the backend records while it runs. The seven-step navigation
 * is coarser than this on purpose: step 6 is one screen for the person, but
 * several recorded operations for the server.
 */
export const PROGRESS_STEPS: ReadonlyArray<{ key: string; label: string }> = [
    { key: 'validate', label: 'Memvalidasi konfigurasi' },
    { key: 'migrate', label: 'Menjalankan migrasi' },
    { key: 'seed_permissions', label: 'Menyiapkan role dan permission' },
    { key: 'seed_categories', label: 'Menyiapkan kategori awal' },
    { key: 'create_admin', label: 'Membuat akun Super Admin' },
    { key: 'save_settings', label: 'Menyimpan identitas paroki' },
    { key: 'verify', label: 'Memverifikasi instalasi' },
    { key: 'lock', label: 'Mengunci installer' },
];
