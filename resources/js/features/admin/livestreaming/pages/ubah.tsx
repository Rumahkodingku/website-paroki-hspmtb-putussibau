import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Text } from '@/components/text';
import { dashboard } from '@/routes';
import {
    index as livestreamsIndex,
    update as updateLivestream,
} from '@/routes/livestreams';
import { toDateTimeLocalValue } from '@/lib/datetime';
import type { SharedProps } from '@/types';
import {
    LivestreamForm,
    submitLivestreamForm,
} from '../components/livestream-form';
import {
    LivestreamBroadcastBadge,
    LivestreamEditorialBadge,
} from '../components/livestream-status-badge';
import type {
    LivestreamFormData,
    LivestreamFormMeta,
    LivestreamRow,
    LocationOption,
} from '../types';

type Props = LivestreamFormMeta & {
    livestream: LivestreamRow;
    locations: LocationOption[];
};

export default function Edit({
    livestream,
    labels,
    hints,
    copy,
    categories,
    statuses,
    locations,
}: Props) {
    const { displayTimezone } = usePage<SharedProps>().props;

    const currentLocationIsActive =
        livestream.location_id === null ||
        locations.some((location) => location.id === livestream.location_id);

    const initial: LivestreamFormData = {
        title: livestream.title,
        youtube_url: livestream.youtube_url,
        description: livestream.description ?? '',
        livestream_category_id: livestream.category_id
            ? String(livestream.category_id)
            : '',
        location_id:
            currentLocationIsActive && livestream.location_id !== null
                ? String(livestream.location_id)
                : '',
        scheduled_at: livestream.scheduled_at
            ? toDateTimeLocalValue(livestream.scheduled_at, displayTimezone)
            : '',
        status: livestream.status,
        recorded_at: livestream.recorded_at
            ? toDateTimeLocalValue(livestream.recorded_at, displayTimezone)
            : '',
        is_recording_available: livestream.is_recording_available,
    };

    return (
        <>
            <Head title={`Ubah ${livestream.title}`} />

            <div className="space-y-6 p-6">
                <div className="flex flex-wrap items-start justify-between gap-4">
                    <div className="space-y-2">
                        <Heading
                            title={copy.edit ?? 'Ubah Livestreaming'}
                            description={`Perbarui metadata "${livestream.title}".`}
                        />

                        <div className="flex flex-wrap items-center gap-2">
                            <LivestreamEditorialBadge
                                status={livestream.status}
                                label={livestream.status_label}
                            />
                            <LivestreamBroadcastBadge
                                status={livestream.broadcast_status}
                                label={livestream.broadcast_status_label}
                            />
                        </div>
                    </div>
                </div>

                {!currentLocationIsActive ? (
                    <div className="rounded-md border border-border bg-canvas-subtle p-4">
                        <Text as="p" variant="body-sm" color="muted">
                            Lokasi sebelumnya
                            {livestream.location
                                ? ` (${livestream.location})`
                                : ''}{' '}
                            sudah nonaktif. Pilih lokasi aktif atau biarkan
                            kosong.
                        </Text>
                    </div>
                ) : null}

                <LivestreamForm
                    initial={initial}
                    labels={labels}
                    hints={hints}
                    copy={copy}
                    categories={categories}
                    statuses={statuses}
                    locations={locations}
                    submitLabel={copy.save ?? 'Simpan'}
                    isLive={livestream.broadcast_status === 'live'}
                    onSubmit={(form) =>
                        submitLivestreamForm(
                            form,
                            updateLivestream(livestream.id),
                        )
                    }
                    onCancel={() => router.visit(livestreamsIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Livestreaming', href: livestreamsIndex() },
        { title: 'Ubah' },
    ],
};
