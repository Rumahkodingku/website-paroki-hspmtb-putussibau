import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { dashboard } from '@/routes';
import {
    activate as activatePeriod,
    create as createPeriod,
    destroy as destroyPeriod,
    edit as editPeriod,
    index as periodsIndex,
} from '@/routes/board-periods';
import {
    create as createMember,
    destroy as destroyMember,
    edit as editMember,
} from '@/routes/board-members';
import { DeleteDialog } from '@/components/delete-dialog';
import { ListEmpty, ListHeader } from '@/components/list-parts';
import { Text } from '@/components/text';
import type { ParishBoardPeriodRow } from '@/features/admin/profil/types';

type Props = {
    periods: ParishBoardPeriodRow[];
    selected: ParishBoardPeriodRow | null;
};

export default function Index({ periods, selected }: Props) {
    const [deletingPeriod, setDeletingPeriod] =
        useState<ParishBoardPeriodRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const selectPeriod = (id: number) => {
        router.get(
            periodsIndex().url,
            { periode: id },
            { preserveScroll: true, preserveState: false },
        );
    };

    return (
        <>
            <Head title="Struktur Kepengurusan" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Struktur Kepengurusan"
                    description="Periode dan anggota kepengurusan paroki."
                    action={
                        <Button asChild>
                            <Link href={createPeriod()}>Tambah periode</Link>
                        </Button>
                    }
                />

                {periods.length === 0 ? (
                    <ListEmpty
                        title="Belum ada periode kepengurusan."
                        description="Tambahkan periode pertama untuk mulai."
                    />
                ) : (
                    <div className="grid gap-4 md:grid-cols-2">
                        {periods.map((period) => (
                            <div
                                key={period.id}
                                className={`rounded-lg border p-4 ${
                                    selected?.id === period.id
                                        ? 'border-primary'
                                        : 'border-border'
                                }`}
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        onClick={() => selectPeriod(period.id)}
                                        className="text-heading-sm hover:underline"
                                    >
                                        {period.name}
                                    </button>
                                    {period.is_active ? (
                                        <Badge>Aktif</Badge>
                                    ) : null}
                                    <Badge
                                        variant={
                                            period.is_published
                                                ? 'default'
                                                : 'secondary'
                                        }
                                    >
                                        {period.is_published
                                            ? 'Terbit'
                                            : 'Draf'}
                                    </Badge>
                                </div>

                                <Text
                                    as="p"
                                    variant="body-sm"
                                    color="muted"
                                    className="mt-1"
                                >
                                    {period.start_year ?? '?'} –{' '}
                                    {period.end_year ?? 'sekarang'} ·{' '}
                                    {period.members_count ??
                                        period.members.length}{' '}
                                    anggota
                                </Text>

                                <div className="mt-4 flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            router.post(
                                                activatePeriod({
                                                    period: period.id,
                                                }),
                                                { preserveScroll: true },
                                            )
                                        }
                                        disabled={period.is_active}
                                    >
                                        Aktifkan
                                    </Button>

                                    <Button asChild variant="outline" size="sm">
                                        <Link
                                            href={editPeriod({
                                                period: period.id,
                                            })}
                                        >
                                            Ubah
                                        </Link>
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            setDeletingPeriod(period)
                                        }
                                    >
                                        Hapus
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}

                {selected ? (
                    <section className="space-y-4 border-t pt-6">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <Text as="h3" variant="heading-md">
                                Anggota - {selected.name}
                            </Text>
                            <Button asChild>
                                <Link
                                    href={createMember({
                                        period: selected.id,
                                    })}
                                >
                                    Tambah anggota
                                </Link>
                            </Button>
                        </div>

                        {selected.members.length === 0 ? (
                            <ListEmpty
                                title="Belum ada anggota pada periode ini."
                                description="Tambahkan anggota untuk menyusun kepengurusan."
                            />
                        ) : (
                            <ul className="divide-y divide-border rounded-lg border border-border">
                                {selected.members.map((member) => (
                                    <li
                                        key={member.id}
                                        className="flex flex-wrap items-center justify-between gap-3 p-4"
                                    >
                                        <div className="flex flex-row items-center gap-3">
                                            {member.photo_url ? (
                                                <img
                                                    src={member.photo_url}
                                                    alt={member.name}
                                                    loading="lazy"
                                                    className="size-16 rounded-md border border-border object-cover"
                                                />
                                            ) : (
                                                <div
                                                    aria-hidden="true"
                                                    className="flex size-16 items-center justify-center rounded-md border border-border bg-muted text-body-sm text-muted-foreground"
                                                >
                                                    —
                                                </div>
                                            )}
                                            <div>
                                                <Text
                                                    as="p"
                                                    variant="heading-sm"
                                                >
                                                    {member.name}
                                                </Text>
                                                <Text
                                                    as="p"
                                                    variant="body-sm"
                                                    color="muted"
                                                >
                                                    {member.position}
                                                    {member.is_published
                                                        ? ''
                                                        : ' · draf'}
                                                </Text>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-3">
                                            {member.can.update ? (
                                                <Button
                                                    asChild
                                                    variant="outline"
                                                    size="sm"
                                                >
                                                    <Link
                                                        href={editMember({
                                                            period: selected.id,
                                                            member: member.id,
                                                        })}
                                                    >
                                                        Ubah
                                                    </Link>
                                                </Button>
                                            ) : null}

                                            {member.can.delete ? (
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => {
                                                        if (
                                                            window.confirm(
                                                                'Hapus anggota ini?',
                                                            )
                                                        ) {
                                                            router.delete(
                                                                destroyMember({
                                                                    period: selected.id,
                                                                    member: member.id,
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
                ) : null}
            </div>

            {deletingPeriod ? (
                <DeleteDialog
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeletingPeriod(null);
                        }
                    }}
                    itemLabel={`Periode "${deletingPeriod.name}"`}
                    consequences={[
                        `${deletingPeriod.members_count ?? deletingPeriod.members.length} anggota`,
                    ]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);
                        router.delete(destroyPeriod(deletingPeriod.id), {
                            preserveScroll: true,
                            onFinish: () => {
                                setSubmitting(false);
                                setDeletingPeriod(null);
                            },
                        });
                    }}
                />
            ) : null}
        </>
    );
}

Index.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Profil Paroki' },
        { title: 'Struktur Kepengurusan' },
    ],
};
