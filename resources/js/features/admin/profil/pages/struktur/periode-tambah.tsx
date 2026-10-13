import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as periodsIndex,
    store as storePeriod,
} from '@/routes/board-periods';
import {
    BoardPeriodForm,
    emptyBoardPeriodFormData,
} from './components/board-period-form';

type Props = {
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Create({ labels, hints }: Props) {
    return (
        <>
            <Head title="Tambah Periode Kepengurusan" />

            <div className="p-6">
                <Heading
                    title="Tambah Periode"
                    description="Periode kepengurusan baru."
                    variant="default"
                />

                <BoardPeriodForm
                    initial={emptyBoardPeriodFormData()}
                    labels={labels}
                    hints={hints}
                    onSubmit={(form) => form.submit(storePeriod())}
                    onCancel={() => router.visit(periodsIndex())}
                />
            </div>
        </>
    );
}

Create.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Struktur Kepengurusan' },
        { title: 'Tambah Periode' },
    ],
};
