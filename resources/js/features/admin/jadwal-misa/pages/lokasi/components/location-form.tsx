import { useForm } from '@inertiajs/react';
import type { LocationType } from '../../../types';
import {
    ActiveSwitch,
    ChoiceSelect,
    Field,
    FormActions,
    FormInput,
    FormTextarea,
} from '../../../components/form-fields';

export type LocationFormInstance = ReturnType<typeof useForm<LocationFormData>>;

export type LocationFormData = {
    name: string;
    slug: string;
    type: LocationType;
    address: string;
    latitude: string;
    longitude: string;
    maps_url: string;
    notes: string;
    is_active: boolean;
};

export function emptyLocationFormData(): LocationFormData {
    return {
        name: '',
        slug: '',
        type: 'gereja_paroki',
        address: '',
        latitude: '',
        longitude: '',
        maps_url: '',
        notes: '',
        is_active: true,
    };
}

type Props = {
    initial: LocationFormData;
    labels: Record<string, string>;
    hints: Record<string, string>;
    types: Array<{ value: LocationType; label: string }>;
    statuses: Record<string, string>;
    submitLabel: string;
    onSubmit: (form: LocationFormInstance) => void;
    onCancel: () => void;
};

export function LocationForm({
    initial,
    labels,
    hints,
    types,
    statuses,
    submitLabel,
    onSubmit,
    onCancel,
}: Props) {
    const form = useForm<LocationFormData>(initial);

    return (
        <form
            noValidate
            onSubmit={(event) => {
                event.preventDefault();
                onSubmit(form);
            }}
            className="space-y-6"
        >
            <div className="grid gap-6 md:grid-cols-2">
                <div className="md:col-span-2">
                    <Field
                        id="location-name"
                        label={labels.name ?? 'Nama lokasi'}
                        error={form.errors.name}
                        required
                    >
                        <FormInput
                            id="location-name"
                            value={form.data.name}
                            onChange={(value) => form.setData('name', value)}
                            placeholder="Masukkan nama lokasi misa"
                            required
                        />
                    </Field>
                </div>

                <Field
                    id="location-slug"
                    label={labels.slug ?? 'Slug'}
                    hint={hints.slug}
                    error={form.errors.slug}
                    required
                >
                    <FormInput
                        id="location-slug"
                        value={form.data.slug}
                        onChange={(value) => form.setData('slug', value)}
                        placeholder="Masukkan slug disini"
                        required
                    />
                </Field>

                <Field
                    id="location-type"
                    label={labels.type ?? 'Jenis lokasi'}
                    hint={hints.type}
                    error={form.errors.type}
                    required
                >
                    <ChoiceSelect
                        id="location-type"
                        value={form.data.type}
                        onChange={(value) =>
                            form.setData('type', value as LocationType)
                        }
                        options={types}
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="location-address"
                        label={labels.address ?? 'Alamat'}
                        error={form.errors.address}
                        required
                    >
                        <FormTextarea
                            id="location-address"
                            rows={2}
                            value={form.data.address}
                            onChange={(value) => form.setData('address', value)}
                            placeholder="Masukkan alamat lokasi misa"
                        />
                    </Field>
                </div>

                <Field
                    id="location-latitude"
                    label={labels.latitude ?? 'Lintang'}
                    hint={hints.latitude}
                    error={form.errors.latitude ?? form.errors.longitude}
                >
                    <FormInput
                        id="location-latitude"
                        inputMode="decimal"
                        value={form.data.latitude}
                        onChange={(value) => form.setData('latitude', value)}
                        placeholder="Masukkan lokasi lintang"
                    />
                </Field>

                <Field
                    id="location-longitude"
                    label={labels.longitude ?? 'Bujur'}
                    hint={hints.longitude}
                    error={form.errors.longitude}
                >
                    <FormInput
                        id="location-longitude"
                        inputMode="decimal"
                        value={form.data.longitude}
                        onChange={(value) => form.setData('longitude', value)}
                        placeholder="Masukkan lokasi bujur disini"
                    />
                </Field>

                <div className="md:col-span-2">
                    <Field
                        id="location-maps-url"
                        label={labels.maps_url ?? 'URL Google Maps'}
                        hint={hints.maps_url}
                        error={form.errors.maps_url}
                        required
                    >
                        <FormInput
                            id="location-maps-url"
                            inputMode="url"
                            value={form.data.maps_url}
                            onChange={(value) =>
                                form.setData('maps_url', value)
                            }
                            placeholder="Masukkan URL Google Maps disini"
                        />
                    </Field>
                </div>

                <div className="md:col-span-2">
                    <Field
                        id="location-notes"
                        label={labels.notes ?? 'Catatan'}
                        error={form.errors.notes}
                    >
                        <FormTextarea
                            id="location-notes"
                            rows={2}
                            value={form.data.notes}
                            onChange={(value) => form.setData('notes', value)}
                            placeholder="Masukkan catatan tambahan disini"
                        />
                    </Field>
                </div>
            </div>

            <ActiveSwitch
                id="location-active"
                checked={form.data.is_active}
                onChange={(checked) => form.setData('is_active', checked)}
                activeLabel={statuses.active ?? 'Aktifkan lokasi'}
            />

            <FormActions
                processing={form.processing}
                submitLabel={submitLabel}
                onCancel={onCancel}
            />
        </form>
    );
}
