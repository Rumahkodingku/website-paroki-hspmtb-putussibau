import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as scheduleNoticesIndex,
    store as storeScheduleNotice,
} from '@/routes/schedule-notices';
import {
    ScheduleNoticeForm,
    emptyScheduleNoticeFormData,
} from '../../components/schedule-notice-form';
import type { FormMeta, NoticeTargets } from '../../types';

/*
 * /admin/pemberitahuan-jadwal/tambah — the create form for a notice.
 *
 * The target picker's options arrive from the server: locations, schedules and
 * upcoming special masses are each a query, and rebuilding them in the browser
 * would mean shipping the parish's whole schedule to render one dropdown.
 */

type Props = Pick<
    FormMeta,
    'labels' | 'hints' | 'statuses' | 'noticeStatuses' | 'noticeScopes'
> & {
    targets: NoticeTargets;
};

export default function Create({
    labels,
    hints,
    statuses,
    noticeScopes,
    targets,
}: Props) {
    return (
        <>
            <Head title="Tambah Pemberitahuan" />

            <Heading title="Tambah Pemberitahuan" variant="small" />

            <div className="p-6">
                <ScheduleNoticeForm
                    initial={emptyScheduleNoticeFormData()}
                    labels={labels}
                    hints={hints}
                    statuses={statuses}
                    noticeScopes={noticeScopes}
                    targets={targets}
                    submitLabel="Simpan pemberitahuan"
                    onSubmit={(form) => form.submit(storeScheduleNotice())}
                    onCancel={() => router.visit(scheduleNoticesIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Pemberitahuan', href: scheduleNoticesIndex() },
        { title: 'Tambah' },
    ],
};
