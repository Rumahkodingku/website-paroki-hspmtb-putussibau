import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { updateNarrative } from '@/actions/App/Http/Controllers/Profil/ParishHistoryItemController';
import type { DataColumn } from '@/components/data-table';
import { DataTable } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import InputError from '@/components/input-error';
import { RichTextEditor } from '@/components/rich-text-editor';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { dashboard } from '@/routes';
import {
    create as createHistory,
    destroy as destroyHistory,
    edit as editHistory,
} from '@/routes/parish-histories';
import {
    ListEmpty,
    ListHeader,
    ListToolbar,
    StatusBadge,
} from '@/components/list-parts';
import { ListPagination } from '@/components/list-pagination';
import { RowActions } from '@/components/row-actions';
import type { Paginated } from '@/types';
import type { ParishHistoryItemRow } from '@/features/admin/profil/types';

type Props = {
    items: Paginated<ParishHistoryItemRow>;
    narrative: string | null;
    filters: { search: string; sort: string; direction: string };
};

export default function Index({ items, narrative, filters }: Props) {
    const [activeTab, setActiveTab] = useState('narasi');
    const [deleting, setDeleting] = useState<ParishHistoryItemRow | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const narrativeForm = useForm<{ narrative: string }>({
        narrative: narrative ?? '',
    });

    const applyFilter = (next: Partial<typeof filters>) => {
        router.get(
            '/admin/profil/sejarah',
            { ...filters, ...next },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const columns: DataColumn<ParishHistoryItemRow>[] = [
        {
            id: 'no',
            accessorKey: 'no',
            header: 'No',
            cell: ({ row }) => row.index + 1,
        },
        {
            id: 'title',
            accessorKey: 'title',
            header: 'Judul peristiwa',
            sortKey: 'title',
            cell: ({ row }) => row.original.title,
        },
        {
            id: 'year',
            accessorKey: 'year',
            header: 'Tahun / tanggal',
            sortKey: 'year',
            cell: ({ row }) => row.original.date ?? row.original.year ?? '—',
        },
        {
            id: 'sort_order',
            accessorKey: 'sort_order',
            header: 'Urutan',
            sortKey: 'sort_order',
            cell: ({ row }) => row.original.sort_order,
        },
        {
            id: 'published',
            header: 'Status',
            cell: ({ row }) => (
                <StatusBadge active={row.original.is_published} />
            ),
        },
        {
            id: 'actions',
            header: 'Aksi',
            cell: ({ row }) => (
                <RowActions
                    canUpdate={row.original.can.update}
                    canDelete={row.original.can.delete}
                    updateHref={editHistory(row.original.id)}
                    onDelete={() => setDeleting(row.original)}
                />
            ),
        },
    ];

    return (
        <>
            <Head title="Sejarah Paroki" />

            <div className="space-y-6 p-6">
                <ListHeader
                    title="Sejarah Paroki"
                    description="Narasi sejarah dan linimasa peristiwa."
                    action={
                        <Button asChild>
                            <Link href={createHistory()}>Tambah peristiwa</Link>
                        </Button>
                    }
                />

                <Tabs value={activeTab} onValueChange={setActiveTab}>
                    <TabsList variant="line" className="mb-8">
                        <TabsTrigger value="narasi">Narasi</TabsTrigger>
                        <TabsTrigger value="peristiwa">Peristiwa</TabsTrigger>
                    </TabsList>

                    <TabsContent value="narasi" className="space-y-4">
                        <form
                            noValidate
                            onSubmit={(event: any) => {
                                event.preventDefault();
                                narrativeForm.submit(updateNarrative(), {
                                    preserveScroll: true,
                                });
                            }}
                            className="space-y-4"
                        >
                            <RichTextEditor
                                id="history-narrative"
                                label="Narasi sejarah"
                                value={narrativeForm.data.narrative}
                                onChange={(html) =>
                                    narrativeForm.setData('narrative', html)
                                }
                            />

                            {narrativeForm.errors.narrative ? (
                                <InputError
                                    message={narrativeForm.errors.narrative}
                                />
                            ) : null}

                            <Button
                                type="submit"
                                disabled={narrativeForm.processing}
                            >
                                Simpan narasi
                            </Button>
                        </form>
                    </TabsContent>

                    <TabsContent value="peristiwa" className="space-y-4">
                        <ListToolbar
                            search={filters.search}
                            searchPlaceholder="Cari judul peristiwa"
                            onSearch={(search) => applyFilter({ search })}
                        />

                        <DataTable
                            columns={columns}
                            rows={items.data}
                            sort={filters.sort}
                            direction={filters.direction}
                            onSort={(sort, direction) =>
                                applyFilter({ sort, direction })
                            }
                            rowId={(row) => row.id}
                            empty={
                                <ListEmpty
                                    title={
                                        filters.search !== ''
                                            ? 'Tidak ada peristiwa untuk pencarian ini.'
                                            : 'Belum ada peristiwa sejarah.'
                                    }
                                    description="Tambahkan peristiwa pertama untuk mulai menyusun linimasa."
                                />
                            }
                        />

                        <ListPagination
                            meta={items.meta}
                            previous={items.links.prev}
                            next={items.links.next}
                        />
                    </TabsContent>
                </Tabs>
            </div>

            {deleting ? (
                <DeleteDialog
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    itemLabel={`Peristiwa "${deleting.title}"`}
                    consequences={[]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);
                        router.delete(destroyHistory(deleting.id), {
                            preserveScroll: true,
                            onFinish: () => {
                                setSubmitting(false);
                                setDeleting(null);
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
        { title: 'Sejarah Paroki' },
    ],
};
