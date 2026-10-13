import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { index as periodsIndex } from '@/routes/board-periods';
import { update as updateMember } from '@/routes/board-members';
import type { ParishBoardMemberRow } from '@/features/admin/profil/types';
import {
    BoardMemberForm,
    boardMemberToFormData,
} from './components/board-member-form';

type Props = {
    period: {
        id: number;
        name: string;
    };
    member: ParishBoardMemberRow;
    labels: Record<string, string>;
    hints: Record<string, string>;
};

export default function Edit({ period, member, labels, hints }: Props) {
    return (
        <>
            <Head title={`Ubah: ${member.name}`} />

            <div className="p-6">
                <Heading
                    title="Ubah Anggota"
                    description={`${member.name} — ${period.name}`}
                    variant="default"
                />

                <BoardMemberForm
                    initial={boardMemberToFormData(member)}
                    currentPhotoUrl={member.photo_url}
                    labels={labels}
                    hints={hints}
                    onSubmit={(form) =>
                        form.submit(
                            updateMember({
                                period: period.id,
                                member: member.id,
                            }),
                        )
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

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Struktur Kepengurusan' },
        { title: 'Ubah Anggota' },
    ],
};
