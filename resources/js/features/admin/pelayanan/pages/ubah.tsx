import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';
import { index as servicesIndex } from '@/routes/services';
import { ServiceForm } from '../components/service-form';
import type {
    ServiceCategoryOption,
    ServiceDetail,
    ServiceDocumentOption,
    ServiceFaqRow,
    ServiceFormMeta,
    ServiceRequirementRow,
    ServiceStepRow,
} from '../types';

type Props = {
    service: ServiceDetail;
    requirements: ServiceRequirementRow[];
    steps: ServiceStepRow[];
    faqs: ServiceFaqRow[];
    categories: ServiceCategoryOption[];
    documents: ServiceDocumentOption[];
} & ServiceFormMeta;

export default function Ubah({
    service,
    requirements,
    steps,
    faqs,
    categories,
    documents,
    labels,
    hints,
    methods,
    copy,
    empty,
}: Props) {
    return (
        <>
            <Head title={`${copy.edit_title}: ${service.title}`} />

            <ServiceForm
                mode="edit"
                service={service}
                requirements={requirements}
                steps={steps}
                faqs={faqs}
                categories={categories}
                documents={documents}
                labels={labels}
                hints={hints}
                methods={methods}
                copy={copy}
                empty={empty}
            />
        </>
    );
}

Ubah.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Pelayanan', href: servicesIndex() },
        { title: 'Edit Layanan' },
    ],
};
