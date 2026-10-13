import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { index as periodsIndex } from '@/routes/board-periods';
import { store as storeMember } from '@/routes/board-members';
import {
    BoardMemberForm,
    emptyBoardMemberFormData,
} from './components/board-member-form';

type Props = {
    period: { id: number; name: string };
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Create({ period, labels, hints }: Props) {
    return (
        <>
            <Head title="Tambah Anggota Kepengurusan" />

            <div className="p-6">
                <Heading
                    title="Tambah Anggota"
                    description={period.name}
                    variant="default"
                />

                <BoardMemberForm
                    initial={emptyBoardMemberFormData()}
                    currentPhotoUrl={null}
                    labels={labels}
                    hints={hints}
                    onSubmit={(form) =>
                        form.submit(storeMember({ period: period.id }))
                    }
                    onCancel={() =>
                        router.visit(
                            periodsIndex({ query: { periode: period.id } }),
                        )
                    }
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
        { title: 'Tambah Anggota' },
    ],
};
