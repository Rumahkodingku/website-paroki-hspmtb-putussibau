import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import {
    index as periodsIndex,
    update as updatePeriod,
} from '@/routes/board-periods';
import type { ParishBoardPeriodRow } from '@/features/admin/profil/types';
import {
    BoardPeriodForm,
    boardPeriodToFormData,
} from './components/board-period-form';

type Props = {
    period: ParishBoardPeriodRow;
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Edit({ period, labels, hints }: Props) {
    return (
        <>
            <Head title={`Ubah: ${period.name}`} />

            <div className="p-6">
                <Heading
                    title="Ubah Periode"
                    description={period.name}
                    variant="default"
                />

                <BoardPeriodForm
                    initial={boardPeriodToFormData(period)}
                    labels={labels}
                    hints={hints}
                    onSubmit={(form) =>
                        form.submit(updatePeriod({ period: period.id }))
                    }
                    onCancel={() => router.visit(periodsIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Struktur Kepengurusan' },
        { title: 'Ubah Periode' },
    ],
};
