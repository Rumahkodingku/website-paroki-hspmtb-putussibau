import type { PublicDocumentCard } from '@/types/document';

/** App\Http\Resources\Public\Pelayanan\PublicServiceCardResource */
export type PublicServiceCard = {
    id: number;
    title: string;
    slug: string;
    thumbnail: string | null;
    thumbnail_alt: string | null;
    summary: string | null;
    is_emergency: boolean;
};

/** One category group on the index, built by PublicServiceController::index. */
export type PublicServiceGroup = {
    id: number;
    name: string;
    description: string;
    slug: string;
    services: PublicServiceCard[];
};

/** The four CTA states of PRD 7.6, decided on the server. */
export type PublicServiceCtaState =
    'form_open' | 'form_closed' | 'contact' | 'emergency';

export type PublicServiceCta = {
    state: PublicServiceCtaState;
    url: string | null;
    label: string;
    message: string | null;
};

export type PublicServiceContact = {
    name: string | null;
    phone: string | null;
    tel: string | null;
    whatsapp: string | null;
    source: 'service' | 'secretariat' | null;
};

export type RequirementCondition = 'required' | 'conditional' | 'upon_request';

export type PublicServiceRequirement = {
    id: number;
    text: string;
    note: string | null;
    requirement_condition: RequirementCondition;
    applies_to: string | null;
};

export type PublicServiceStep = {
    id: number;
    title: string;
    description: string | null;
};

export type PublicServiceFaq = {
    id: number;
    question: string;
    answer: string;
};

/** App\Http\Resources\Public\Pelayanan\PublicServiceDetailResource */
export type PublicServiceDetail = {
    id: number;
    title: string;
    slug: string;
    thumbnail: string | null;
    thumbnail_alt: string | null;
    summary: string | null;
    introduction: string | null;
    audience_info: string | null;
    preparation_info: string | null;
    schedule_info: string | null;
    fee_info: string | null;
    is_emergency: boolean;
    category?: { id: number; name: string; slug: string };
    cta: PublicServiceCta;
    contact: PublicServiceContact;
    requirements: PublicServiceRequirement[];
    steps: PublicServiceStep[];
    faqs: PublicServiceFaq[];
    documents: PublicDocumentCard[];
    updated_at: string | null;
    seo: { title: string; description: string };
};
