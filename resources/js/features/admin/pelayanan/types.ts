/*
 * Types only the Pelayanan admin feature needs.
 *
 * They mirror the Phase 6B resources in app/Http/Resources/Pelayanan one for
 * one. When a resource gains a field, the matching type here changes in the same
 * commit — ARCHITECTURE.md Part C section 1.
 *
 * @see docs/DECISIONS.md D-54
 */

/** How a visitor registers. Derived, never stored — see ServiceListResource. */
export type RegistrationMethod = 'google_form' | 'direct_contact' | 'unset';

/** App\Http\Resources\Pelayanan\ServiceListResource */
export type ServiceListItem = {
    id: number;
    title: string;
    slug: string;
    thumbnail: string | null;
    thumbnail_alt: string | null;
    category?: { id: number; name: string };
    registration_method: RegistrationMethod;
    is_active: boolean;
    is_emergency: boolean;
    sort_order: number;
    updated_at: string | null;
    can: { update: boolean; delete: boolean };
};

/** App\Http\Resources\Pelayanan\ServiceResource */
export type ServiceDetail = {
    id: number;
    title: string;
    slug: string;
    service_category_id: number;
    thumbnail_media_id: number | null;
    thumbnail_alt: string | null;
    thumbnail_url: string | null;
    summary: string | null;
    introduction: string | null;
    schedule_info: string | null;
    fee_info: string | null;
    contact_name: string | null;
    contact_phone: string | null;
    form_url: string | null;
    form_button_label: string;
    is_form_open: boolean;
    form_closed_message: string | null;
    is_emergency: boolean;
    is_active: boolean;
    sort_order: number;
    updated_at: string | null;
    category?: { id: number; name: string };
    documents: ServiceDocumentOption[];
    document_ids: number[];
    can: { update: boolean; delete: boolean };
};

export type ServiceDocumentOption = {
    id: number;
    title: string;
    status: 'draft' | 'published' | 'archived';
    status_label: string;
};

/** App\Http\Resources\Pelayanan\ServiceRequirementResource */
export type ServiceRequirementRow = {
    id: number;
    text: string;
    note: string | null;
    sort_order: number;
};

/** App\Http\Resources\Pelayanan\ServiceStepResource */
export type ServiceStepRow = {
    id: number;
    title: string;
    description: string | null;
    sort_order: number;
};

/** App\Http\Resources\Pelayanan\ServiceFaqResource */
export type ServiceFaqRow = {
    id: number;
    question: string;
    answer: string;
    sort_order: number;
};

/** App\Http\Resources\Pelayanan\ServiceOptionResource */
export type ServiceCategoryOption = {
    id: number;
    name: string;
};

/** App\Concerns\ServiceFormMeta */
export type ServiceFormMeta = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    methods: Record<string, RegistrationMethod>;
    /** Interface copy: headings, buttons, dialogs, tabs, placeholders. */
    copy: Record<string, string>;
    /** Empty-state copy. */
    empty: Record<string, string>;
};

export type ServiceFilters = {
    search: string;
    category: string;
    status: string;
    sort: string;
    direction: string;
};

export type ServiceFormData = {
    title: string;
    slug: string;
    service_category_id: string;
    thumbnail_media_id: number | null;
    thumbnail_alt: string;
    summary: string;
    introduction: string;
    schedule_info: string;
    fee_info: string;
    contact_name: string;
    contact_phone: string;
    form_url: string;
    form_button_label: string;
    is_form_open: boolean;
    form_closed_message: string;
    is_emergency: boolean;
    is_active: boolean;
    sort_order: string;
    document_ids: number[];
};
