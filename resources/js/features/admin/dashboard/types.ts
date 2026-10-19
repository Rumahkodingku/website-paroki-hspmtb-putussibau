/*
 * The dashboard's data contract.
 *
 * Every field mirrors a key the PHP controller sends, and every list is
 * bounded there. Nothing here is computed from a paginated payload: the summary
 * counts arrive finished, and the panels receive at most the rows the panel
 * shows.
 *
 * Status values are the enum values from each module, and `status_label` is the
 * Indonesian label produced by the enum itself, so the dashboard never holds a
 * second copy of "Terbit" or "Dibatalkan".
 */

export type PostStatus = 'draft' | 'scheduled' | 'published' | 'archived';

export type EventStatus = 'scheduled' | 'cancelled' | 'postponed';

export type DocumentStatus = 'draft' | 'published' | 'archived';

export type PostSummary = {
    total: number;
    draft: number;
    scheduled: number;
    published: number;
    archived: number;
};

export type EventSummary = {
    upcoming: number;
    draft: number;
    cancelled: number;
    postponed: number;
};

export type DocumentSummary = {
    total: number;
    draft: number;
    published: number;
    archived: number;
    expired: number;
};

export type MassScheduleSummary = {
    regular: number;
    special: number;
    notices: number;
};

export type ServiceSummary = {
    active: number;
    inactive: number;
};

export type DashboardSummary = {
    posts: PostSummary;
    events: EventSummary;
    documents: DocumentSummary;
    massSchedules: MassScheduleSummary;
    services: ServiceSummary;
};

export type UpcomingEvent = {
    id: number;
    title: string;
    status: EventStatus;
    status_label: string;
    is_published: boolean;
    start_at: string;
    end_at: string | null;
    is_all_day: boolean;
    location_name: string | null;
};

export type RecentPost = {
    id: number;
    title: string;
    status: PostStatus;
    status_label: string;
    published_at: string | null;
    updated_at: string;
    image: string | null;
    image_alt: string | null;
};

/** A reference to a row the admin can open and fix. */
export type ContentRef = {
    type: DashboardContentType;
    id: number;
    title: string;
};

export type DashboardContentType = 'post' | 'event' | 'document' | 'service';

/**
 * An attention indicator. `items` is empty for a pure count, and carries the
 * rows to link to when the indicator is a list.
 */
export type AttentionItem = {
    key: 'expired_documents' | 'media_alt_text';
    count: number;
    items: ContentRef[];
};

/** A row in the merged Konten Terbaru panel. */
export type RecentContentItem = {
    type: 'post' | 'event' | 'document';
    id: number;
    title: string;
    status: string;
    status_label: string;
    /** Only defined for events; null for articles and documents. */
    is_published: boolean | null;
    updated_at: string;
};

export type DashboardProps = {
    summary: DashboardSummary;
    upcomingEvents: UpcomingEvent[];
    recentPosts: RecentPost[];
    attentionItems: AttentionItem[];
    recentContent: RecentContentItem[];
};
