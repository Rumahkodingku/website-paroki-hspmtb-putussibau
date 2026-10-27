import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import { DataTable } from '@/components/data-table';
import { DeleteDialog } from '@/components/delete-dialog';
import {
    FilterSelect,
    ListEmpty,
    ListHeader,
    ListToolbar,
    ResetFilters,
} from '@/components/list-parts';
import { ListPagination } from '@/components/list-pagination';
import { RowActions } from '@/components/row-actions';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { show as showService } from '@/routes/pelayanan';
import {
    create as createService,
    destroy as destroyService,
    edit as editService,
    index as servicesIndex,
} from '@/routes/services';
import { ServiceCards } from '../components/service-cards';
import { serviceColumns } from '../components/service-columns';
import type {
    ServiceCategoryOption,
    ServiceFilters,
    ServiceFormMeta,
    ServiceListItem,
} from '../types';
import type { Paginated } from '@/types';

type Props = {
    services: Paginated<ServiceListItem>;
    categories: ServiceCategoryOption[];
    filters: ServiceFilters;
} & Pick<ServiceFormMeta, 'methods' | 'copy' | 'empty'>;

export default function Index({
    services,
    categories,
    filters,
    methods,
    copy,
    empty,
}: Props) {
    const [deleting, setDeleting] = useState<ServiceListItem | null>(null);
    const [submitting, setSubmitting] = useState(false);

    const applyFilter = (next: Partial<ServiceFilters>) => {
        router.get(
            servicesIndex().url,
            { ...filters, ...next, page: 1 },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const sortBy = (sort: string, direction: 'asc' | 'desc') => {
        router.get(
            servicesIndex().url,
            { ...filters, sort, direction, page: 1 },
            { preserveScroll: true, replace: true },
        );
    };

    const hasFilter =
        filters.search !== '' ||
        filters.status !== '' ||
        filters.category !== '';

    const statusOptions = [
        { value: '', label: copy.filter_all_status },
        { value: 'active', label: copy.filter_active },
        { value: 'inactive', label: copy.filter_inactive },
    ];

    const categoryOptions = [
        { value: '', label: copy.filter_all_categories },
        ...categories.map((category) => ({
            value: String(category.id),
            label: category.name,
        })),
    ];

    const actions = (row: ServiceListItem) => (
        <div className="flex items-center justify-start gap-4">
            {row.is_active ? (
                <Tooltip>
                    <TooltipTrigger asChild>
                        <a
                            href={showService({ service: row.slug }).url}
                            target="_blank"
                            rel="noopener noreferrer"
                            aria-label={copy.preview_site}
                            className="rounded-sm text-muted-foreground hover:text-primary-on-surface focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <Eye className="size-4" aria-hidden="true" />
                        </a>
                    </TooltipTrigger>
                    <TooltipContent>
                        <p>{copy.preview_site}</p>
                    </TooltipContent>
                </Tooltip>
            ) : null}

            <RowActions
                canUpdate={row.can.update}
                canDelete={row.can.delete}
                updateHref={editService(row.id)}
                onDelete={() => setDeleting(row)}
            />
        </div>
    );

    return (
        <>
            <Head title={copy.list_title} />

            <div className="space-y-6 p-6">
                <ListHeader
                    title={copy.list_title}
                    description={copy.list_description}
                    action={
                        <Button asChild>
                            <Link href={createService()}>
                                {copy.add_service}
                            </Link>
                        </Button>
                    }
                />

                <ListToolbar
                    search={filters.search}
                    searchPlaceholder={copy.search_placeholder}
                    onSearch={(search) => applyFilter({ search })}
                >
                    <FilterSelect
                        id="filter-kategori"
                        label={copy.filter_all_categories}
                        value={filters.category}
                        onChange={(category) => applyFilter({ category })}
                        options={categoryOptions}
                    />

                    <FilterSelect
                        id="filter-status"
                        label={copy.filter_all_status}
                        value={filters.status}
                        onChange={(status) => applyFilter({ status })}
                        options={statusOptions}
                    />

                    {hasFilter || filters.sort !== '' ? (
                        <ResetFilters
                            onReset={() =>
                                applyFilter({
                                    search: '',
                                    status: '',
                                    category: '',
                                    sort: '',
                                    direction: '',
                                })
                            }
                        />
                    ) : null}
                </ListToolbar>

                <ServiceCards
                    rows={services.data}
                    methods={methods}
                    copy={copy}
                />

                <div className="hidden md:block">
                    <DataTable
                        columns={serviceColumns(copy, methods, actions)}
                        rows={services.data}
                        sort={filters.sort}
                        direction={filters.direction}
                        onSort={sortBy}
                        rowId={(row) => row.id}
                        empty={
                            <ListEmpty
                                title={
                                    hasFilter ? empty.list_filtered : empty.list
                                }
                                description={
                                    hasFilter
                                        ? empty.list_filtered_description
                                        : empty.list_description
                                }
                            />
                        }
                    />
                </div>

                <ListPagination
                    meta={services.meta}
                    previous={services.links.prev}
                    next={services.links.next}
                />
            </div>

            {deleting ? (
                <DeleteDialog
                    open
                    onOpenChange={(open) => {
                        if (!open) {
                            setDeleting(null);
                        }
                    }}
                    itemLabel={`Layanan "${deleting.title}"`}
                    consequences={[]}
                    isSubmitting={submitting}
                    onConfirm={() => {
                        setSubmitting(true);

                        router.delete(destroyService(deleting.id), {
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
        { title: 'Pelayanan', href: servicesIndex() },
        { title: 'Daftar Pelayanan' },
    ],
};
