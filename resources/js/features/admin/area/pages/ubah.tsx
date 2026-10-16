import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { dashboard } from '@/routes';
import { index as areasIndex, update as updateArea } from '@/routes/areas';
import { AreaForm, type AreaFormInstance } from '../components/area-form';
import type {
    AreaFormData,
    AreaOption,
    AreaRow,
    AreaTypeOption,
} from '../types';

type Props = {
    area: AreaRow;
    labels: Record<string, string>;
    hints: Record<string, string>;
    areaTypes: AreaTypeOption[];
    parents: AreaOption[];
};

export default function Edit({
    area,
    labels,
    hints,
    areaTypes,
    parents,
}: Props) {
    const initial: AreaFormData = {
        name: area.name,
        slug: area.slug,
        type: area.type,
        parent_id: area.parent_id === null ? '' : String(area.parent_id),
        description: area.description ?? '',
        address: area.address ?? '',
        // The resource sends the decimals as strings, which is what the decimal
        // cast produces; the form holds strings either way.
        latitude: area.latitude ?? '',
        longitude: area.longitude ?? '',
        maps_url: area.maps_url ?? '',
        leader_name: area.leader_name ?? '',
        contact_phone: area.contact_phone ?? '',
        show_contact: area.show_contact,
        sort_order: String(area.sort_order),
        is_active: area.is_active,
    };

    const submit = (form: AreaFormInstance) => {
        form.submit(updateArea(area.id));
    };

    return (
        <>
            <Head title={`Ubah Area ${area.name}`} />

            <div className="p-6">
                <Heading
                    title="Ubah Stasi/Lingkungan"
                    description={`Perbarui area "${area.name}".`}
                />

                <AreaForm
                    initial={initial}
                    labels={labels}
                    hints={hints}
                    areaTypes={areaTypes}
                    parents={parents}
                    submitLabel="Simpan perubahan"
                    onSubmit={submit}
                    onCancel={() => router.visit(areasIndex())}
                />
            </div>
        </>
    );
}

Edit.layout = {
    breadcrumbs: [
        { title: 'Beranda', href: dashboard() },
        { title: 'Area', href: areasIndex() },
        { title: 'Ubah' },
    ],
};
