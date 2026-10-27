import { useForm } from '@inertiajs/react';
import {
    ActiveSwitch,
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '@/components/form-fields';
import { Text } from '@/components/text';
import type { AreaFormData, AreaOption, AreaTypeOption } from '../types';

export type AreaFormInstance = ReturnType<typeof useForm<AreaFormData>>;

export function emptyAreaFormData(): AreaFormData {
    return {
        name: '',
        slug: '',
        type: 'lingkungan',
        parent_id: '',
        description: '',
        address: '',
        latitude: '',
        longitude: '',
        maps_url: '',
        leader_name: '',
        contact_phone: '',
        show_contact: false,
        sort_order: '0',
        is_active: true,
    };
}

type Props = {
    initial: AreaFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    areaTypes: AreaTypeOption[];
    parents: AreaOption[];
    submitLabel: string;
    onSubmit: (form: AreaFormInstance) => void;
    onCancel: () => void;
};

export function AreaForm({
    initial,
    labels,
    hints,
    areaTypes,
    parents,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<AreaFormData>(initial);

    const isLingkungan = form.data.type === 'lingkungan';

    const parentOptions = parents.map((parent) => ({
        value: String(parent.id),
        label: parent.name,
    }));

    return (
        <form
            noValidate
            onSubmit={(event: any) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <div className="grid gap-6 md:grid-cols-2">
                <Field
                    id="area-type"
                    label={labels.type ?? 'Tipe Area'}
                    error={form.errors.type}
                    required
                >
                    <ChoiceSelect
                        id="area-type"
                        value={form.data.type}
                        options={areaTypes.map((option) => ({
                            value: option.value,
                            label: option.label,
                        }))}
                        onChange={(value) => {
                            form.setData('type', value as AreaFormData['type']);

                            // A parent is only meaningful for a lingkungan;
                            // clearing it keeps the payload honest when the type
                            // changes back to wilayah/stasi.
                            if (value !== 'lingkungan') {
                                form.setData('parent_id', '');
                            }
                        }}
                        placeholder="Pilih tipe area"
                    />
                </Field>

                {isLingkungan ? (
                    <Field
                        id="area-parent"
                        label={labels.parent ?? 'Wilayah Induk'}
                        hint={hints.parent}
                        error={form.errors.parent_id}
                        required
                    >
                        <ChoiceSelect
                            id="area-parent"
                            value={form.data.parent_id}
                            placeholder="Pilih wilayah"
                            options={parentOptions}
                            onChange={(value) =>
                                form.setData('parent_id', value)
                            }
                        />
                    </Field>
                ) : null}

                <Field
                    id="area-name"
                    label={labels.name ?? 'Nama'}
                    error={form.errors.name}
                    required
                >
                    <FormInput
                        id="area-name"
                        value={form.data.name}
                        onChange={(value) => form.setData('name', value)}
                        required
                        placeholder="Contoh: Wilayah Santo Yosef"
                    />
                </Field>

                <Field
                    id="area-leader-name"
                    label={labels.leader_name ?? 'Nama Ketua'}
                    error={form.errors.leader_name}
                >
                    <FormInput
                        id="area-leader-name"
                        value={form.data.leader_name}
                        onChange={(value) => form.setData('leader_name', value)}
                        placeholder="Contoh: Bapak Yohanes"
                    />
                </Field>

                <Field
                    id="area-contact-phone"
                    label={labels.contact_phone_area ?? 'Nomor Kontak Ketua'}
                    error={form.errors.contact_phone}
                >
                    <FormInput
                        id="area-contact-phone"
                        value={form.data.contact_phone}
                        onChange={(value) =>
                            form.setData('contact_phone', value)
                        }
                        inputMode="numeric"
                        placeholder="Contoh: 08123456789"
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="area-description"
                        label={labels.description ?? 'Deskripsi'}
                        error={form.errors.description}
                    >
                        <FormTextarea
                            id="area-description"
                            value={form.data.description}
                            onChange={(value) =>
                                form.setData('description', value)
                            }
                            rows={2}
                            placeholder="Contoh: Deskripsi wilayah/lingkungan"
                        />
                    </Field>
                </div>
            </div>

            {/*
                Where the area is (D-61). A titled block rather than four more
                fields in the grid above: coordinates next to "Urutan Tampilan"
                read as one list, and the only rule anybody has to learn here -
                that latitude and longitude travel together - is easier to see
                when they sit next to each other with a heading over them.
            */}
            <section className="space-y-6 rounded-lg border border-border p-6">
                <div className="space-y-1">
                    <Text as="h3" variant="h3">
                        {labels.location_heading ?? 'Lokasi dan Peta'}
                    </Text>
                    <Text as="p" variant="body-sm" color="muted">
                        {labels.location_description ??
                            'Alamat dan titik peta area ini. Lintang dan bujur harus diisi bersama, atau dikosongkan bersama.'}
                    </Text>
                </div>

                <Field
                    id="area-address"
                    label={labels.address ?? 'Alamat'}
                    hint={hints.address}
                    error={form.errors.address}
                >
                    <FormTextarea
                        id="area-address"
                        value={form.data.address}
                        onChange={(value) => form.setData('address', value)}
                        rows={2}
                        placeholder="Nama jalan, nomor, kelurahan"
                    />
                </Field>

                <div className="grid gap-6 md:grid-cols-2">
                    {/*
                        The longitude error is shown on the latitude field too:
                        the pair rule reports the missing half on longitude, and
                        an admin fixing coordinates is looking at both boxes. This
                        is what location-form.tsx does.
                    */}
                    <Field
                        id="area-latitude"
                        label={labels.latitude ?? 'Lintang'}
                        hint={hints.latitude}
                        error={form.errors.latitude ?? form.errors.longitude}
                    >
                        <FormInput
                            id="area-latitude"
                            inputMode="decimal"
                            value={form.data.latitude}
                            onChange={(value) =>
                                form.setData('latitude', value)
                            }
                            placeholder="Contoh: -0.4312900"
                        />
                    </Field>

                    <Field
                        id="area-longitude"
                        label={labels.longitude ?? 'Bujur'}
                        hint={hints.longitude}
                        error={form.errors.longitude}
                    >
                        <FormInput
                            id="area-longitude"
                            inputMode="decimal"
                            value={form.data.longitude}
                            onChange={(value) =>
                                form.setData('longitude', value)
                            }
                            placeholder="Contoh: 111.4935200"
                        />
                    </Field>
                </div>

                <Field
                    id="area-maps-url"
                    label={labels.maps_url ?? 'Tautan Google Maps'}
                    hint={hints.maps_url}
                    error={form.errors.maps_url}
                >
                    <FormInput
                        id="area-maps-url"
                        type="url"
                        inputMode="url"
                        value={form.data.maps_url}
                        onChange={(value) => form.setData('maps_url', value)}
                        placeholder="https://maps.app.goo.gl/..."
                    />
                </Field>
            </section>

            <ActiveSwitch
                id="area-show-contact"
                checked={form.data.show_contact}
                onChange={(checked) => form.setData('show_contact', checked)}
                activeLabel={
                    labels.show_contact_area ??
                    'Tampilkan kontak ketua di halaman publik'
                }
            />

            <ActiveSwitch
                id="area-active"
                checked={form.data.is_active}
                onChange={(checked) => form.setData('is_active', checked)}
                activeLabel={labels.is_active ?? 'Area aktif'}
            />

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
