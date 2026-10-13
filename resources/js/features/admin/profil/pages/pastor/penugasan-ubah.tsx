import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { edit as editPastor } from '@/routes/pastors';
import { update as updateAssignment } from '@/routes/pastor-assignments';
import {
    PastorAssignmentForm,
    pastorAssignmentToFormData,
} from '@/features/admin/profil/pages/pastor/components/pastor-assignment-form';
import type { PastorAssignmentRow } from '@/features/admin/profil/types';

type Props = {
    pastor: {
        id: number;
        name: string;
    };
    assignment: PastorAssignmentRow;
    labels: Record<string, string>;
    positions: Array<{
        value: string;
        label: string;
    }>;
};

export default function EditAssignment({
    pastor,
    assignment,
    labels,
    positions,
}: Props) {
    return (
        <>
            <Head title={`Ubah Penugasan: ${pastor.name}`} />

            <div className="p-6">
                <Heading
                    title="Ubah Penugasan"
                    description={pastor.name}
                    variant="default"
                />

                <PastorAssignmentForm
                    initial={pastorAssignmentToFormData(assignment)}
                    positions={positions}
                    labels={labels}
                    onSubmit={(form) =>
                        form.submit(
                            updateAssignment({
                                pastor: pastor.id,
                                assignment: assignment.id,
                            }),
                        )
                    }
                    onCancel={() =>
                        router.visit(editPastor({ pastor: pastor.id }))
                    }
                />
            </div>
        </>
    );
}

EditAssignment.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Pastor Paroki' },
        { title: 'Ubah Penugasan' },
    ],
};
