/*
 * Types the Livestreaming admin screens need.
 *
 * Mirrors App\Http\Resources\Livestream\LivestreamResource and the form meta
 * sent by LivestreamController. See docs/DECISIONS.md D-101.
 */

export type LivestreamEditorialStatus = 'draft' | 'published' | 'archived';

export type LivestreamBroadcastStatus =
    'scheduled' | 'live' | 'ended' | 'unavailable';

export type LivestreamRow = {
    id: number;
    title: string;
    description: string | null;
    youtube_url: string;
    youtube_video_id: string;
    thumbnail_url: string | null;
    location_id: number | null;
    location: string | null;
    category: string | null;
    category_label: string | null;
    status: LivestreamEditorialStatus;
    status_label: string;
    broadcast_status: LivestreamBroadcastStatus;
    broadcast_status_label: string;
    scheduled_at: string | null;
    started_at: string | null;
    ended_at: string | null;
    recorded_at: string | null;
    is_recording_available: boolean;
    published_at: string | null;
    updated_at: string;
    can: {
        update: boolean;
        delete: boolean;
    };
};

export type LivestreamFormData = {
    title: string;
    youtube_url: string;
    description: string;
    category: string;
    location_id: string;
    scheduled_at: string;
    status: LivestreamEditorialStatus;
    recorded_at: string;
    is_recording_available: boolean;
};

export type LocationOption = {
    id: number;
    name: string;
    type: string;
};

export type LivestreamOption = {
    value: string;
    label: string;
};

export type LivestreamFormMeta = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    copy: Record<string, string>;
    empty: Record<string, string>;
    categories: LivestreamOption[];
    statuses: LivestreamOption[];
    broadcastStatuses: LivestreamOption[];
};

export type ActiveLive = {
    id: number;
    title: string;
};

export type LivestreamFilters = {
    search: string;
    status: string;
    broadcast_status: string;
    kategori: string;
    sort: string;
    direction: string;
};
