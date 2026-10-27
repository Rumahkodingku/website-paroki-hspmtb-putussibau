import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import { index as pastorsIndex } from '@/routes/pastors';
import {
    activate as activateAssignment,
    destroy as destroyAssignment,
    edit as editAssignment,
    store as storeAssignment,
} from '@/routes/pastor-assignments';
import { DeleteDialog } from '@/components/delete-dialog';
import { Text } from '@/components/text';
import {
    emptyPastorAssignmentFormData,
    PastorAssignmentForm,
} from './components/pastor-assignment-form';
import type { PastorAssignmentRow } from '../../types';

type Props = {
    pastor: {
        id: number;
        name: string;
    };
    assignments: PastorAssignmentRow[];
    labels: Record<string, string>;
    hints: Record<string, string>;
    positions: Array<{ value: string; label: string }>;
};

export default function CreateAssignment({
    pastor,
    assignments,
    labels,
    positions,
}: Props) {
    const [deleting, setDeleting] = useState<PastorAssignmentRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    return (
        <>
            <Head title={`Tambah Penugasan: ${pastor.name}`} />

            <div className="space-y-10 p-6">
                <section>
                    <Heading
                        title="Tambah Penugasan"
                        description={pastor.name}
                        variant="default"
                    />

                    <PastorAssignmentForm
                        initial={emptyPastorAssignmentFormData()}
                        positions={positions}
                        labels={labels}
                        onSubmit={(form) =>
                            form.submit(storeAssignment({ pastor: pastor.id }))
                        }
                        onCancel={() => router.visit(pastorsIndex())}
                    />
                </section>

                <section className="border-t pt-8">
                    <Heading
                        title="Riwayat Penugasan"
                        description="Satu pastor boleh memiliki beberapa periode. Hanya satu penugasan per pastor yang boleh berstatus sedang bertugas."
                        variant="default"
                    />

                    {assignments.length === 0 ? (
                        <Text as="p" variant="body-sm" color="muted">
                            Belum ada riwayat penugasan.
                        </Text>
                    ) : (
                        <ul className="space-y-3">
                            {assignments.map((assignment) => (
                                <li
                                    key={assignment.id}
                                    className="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-4"
                                >
                                    <div className="space-y-1">
                                        <div className="flex items-center gap-2">
                                            <Text
                                                as="span"
                                                variant="heading-sm"
                                            >
                                                {assignment.position_label}
                                            </Text>
                                            {assignment.is_current ? (
                                                <Badge>Sedang bertugas</Badge>
                                            ) : null}
                                        </div>
                                        <Text
                                            as="p"
                                            variant="body-sm"
                                            color="muted"
                                        >
                                            {assignment.start_year ??
                                                'Tahun tidak diketahui'}
                                            {assignment.end_year
                                                ? ` – ${assignment.end_year}`
                                                : ' – sekarang'}
                                        </Text>
                                        {assignment.description ? (
                                            <Text
                                                as="p"
                                                variant="body-sm"
                                                color="muted"
                                            >
                                                {assignment.description}
                                            </Text>
                                        ) : null}
                                    </div>

                                    <div className="flex items-center gap-3">
                                        {assignment.can.update &&
                                        !assignment.is_current ? (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    router.post(
                                                        activateAssignment({
                                                            pastor: pastor.id,
                                                            assignment:
                                                                assignment.id,
                                                        }),
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Tetapkan aktif
                                            </Button>
                                        ) : null}

                                        {assignment.can.update ? (
                                            <Button asChild variant="outline">
                                                <Link
                                                    href={editAssignment({
                                                        pastor: pastor.id,
                                                        assignment:
                                                            assignment.id,
                                                    })}
                                                >
                                                    Ubah
                                                </Link>
                                            </Button>
                                        ) : null}

                                        {assignment.can.delete ? (
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() => {
                                                    if (
                                                        window.confirm(
                                                            'Hapus riwayat penugasan ini?',
                                                        )
                                                    ) {
                                                        router.delete(
                                                            destroyAssignment({
                                                                pastor: pastor.id,
                                                                assignment:
                                                                    assignment.id,
                                                            }),
                                                            {
                                                                preserveScroll: true,
                                                            },
                                                        );
                                                    }
                                                }}
                                            >
                                                Hapus
                                            </Button>
                                        ) : null}
                                    </div>
                                </li>
                            ))}
                        </ul>
                    )}
                </section>
            </div>

            {deleting ? (
                <DeleteDialog
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    itemLabel={`Penugasan "${deleting.position_label}"`}
                    consequences={[]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);
                        router.delete(
                            destroyAssignment({
                                pastor: pastor.id,
                                assignment: deleting.id,
                            }),
                            {
                                preserveScroll: true,
                                onFinish: () => {
                                    setSubmitting(false);
                                    setDeleting(null);
                                },
                            },
                        );
                    }}
                />
            ) : null}
        </>
    );
}

CreateAssignment.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Pastor Paroki' },
        { title: 'Tambah Penugasan' },
    ],
};
