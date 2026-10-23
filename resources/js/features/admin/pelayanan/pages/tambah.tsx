import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';
import { index as servicesIndex } from '@/routes/services';
import { ServiceForm } from '../components/service-form';
import type { ServiceCategoryOption, ServiceFormMeta } from '../types';

type Props = {
    categories: ServiceCategoryOption[];
} & ServiceFormMeta;

export default function Tambah({
    categories,
    labels,
    hints,
    methods,
    conditions,
    copy,
    empty,
}: Props) {
    return (
        <>
            <Head title={copy.create_title} />

            <ServiceForm
                mode="create"
                categories={categories}
                labels={labels}
                hints={hints}
                methods={methods}
                conditions={conditions}
                copy={copy}
                empty={empty}
            />
        </>
    );
}

Tambah.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Pelayanan', href: servicesIndex() },
        { title: 'Tambah Layanan' },
    ],
};
