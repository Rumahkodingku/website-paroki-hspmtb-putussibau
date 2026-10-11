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
} from './components/schedule-notice-form';
import type { FormMeta, NoticeTargets } from '../../types';

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

            <div className="p-6">
                <Heading
                    title="Tambah Pemberitahuan"
                    description="Lengkapi form untuk menambahkan pemberitahuan baru"
                />
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
