import { Head, router, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { toDateTimeLocalValue } from '@/lib/datetime';
import { dashboard } from '@/routes';
import {
    index as scheduleNoticesIndex,
    update as updateScheduleNotice,
} from '@/routes/schedule-notices';
import {
    ScheduleNoticeForm,
    columnsToTarget,
    type ScheduleNoticeFormData,
} from '../../components/schedule-notice-form';
import type { FormMeta, NoticeTargets, ScheduleNoticeRow } from '../../types';

/*
 * /admin/pemberitahuan-jadwal/{schedule_notice}/ubah — the edit form.
 *
 * The stored notice's three nullable columns are folded back into the single
 * `target` value the picker works with, so an edit form opens showing what was
 * saved rather than an empty picker that would silently clear the target on the
 * first save.
 */

type Props = {
    notice: ScheduleNoticeRow;
    targets: NoticeTargets;
} & Pick<
    FormMeta,
    'labels' | 'hints' | 'statuses' | 'noticeStatuses' | 'noticeScopes'
>;

export default function Edit({
    notice,
    targets,
    labels,
    hints,
    statuses,
    noticeScopes,
}: Props) {
    const { displayTimezone } = usePage().props;

    const target = columnsToTarget(notice);

    const initial: ScheduleNoticeFormData = {
        title: notice.title,
        message: notice.message,
        target,
        location_id:
            notice.location_id === null ? '' : String(notice.location_id),
        mass_schedule_id:
            notice.mass_schedule_id === null
                ? ''
                : String(notice.mass_schedule_id),
        special_mass_id:
            notice.special_mass_id === null
                ? ''
                : String(notice.special_mass_id),
        starts_at: toDateTimeLocalValue(notice.starts_at, displayTimezone),
        ends_at: notice.ends_at
            ? toDateTimeLocalValue(notice.ends_at, displayTimezone)
            : '',
        is_active: notice.is_active,
    };

    return (
        <>
            <Head title={`Ubah ${notice.title}`} />

            <Heading title={`Ubah ${notice.title}`} variant="small" />

            <div className="p-6">
                <ScheduleNoticeForm
                    initial={initial}
                    labels={labels}
                    hints={hints}
                    statuses={statuses}
                    noticeScopes={noticeScopes}
                    targets={targets}
                    submitLabel="Simpan perubahan"
                    onSubmit={(form) =>
                        form.submit(updateScheduleNotice(notice.id))
                    }
                    onCancel={() => router.visit(scheduleNoticesIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Pemberitahuan', href: scheduleNoticesIndex() },
        { title: 'Ubah' },
    ],
};
