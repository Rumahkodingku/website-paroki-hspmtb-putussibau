import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Text } from '@/components/text';
import { ChoiceSelect } from '@/components/form-fields';
import type { LocationOption } from '../types';

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    locations: LocationOption[];
    scheduleName: string;
    scheduleTime: string;
    dayLabel: string;
    isSubmitting: boolean;
    errors?: { location_id?: string };
    onSubmit: (locationId: number) => void;
};

export function DuplicateMassScheduleDialog({
    open,
    onOpenChange,
    locations,
    scheduleName,
    scheduleTime,
    dayLabel,
    isSubmitting,
    errors,
    onSubmit,
}: Props) {
    const [locationId, setLocationId] = useState('');

    return (
        <ConfirmDialog
            open={open}
            onOpenChange={onOpenChange}
            title="Duplikasi jadwal"
            description={
                <>
                    <Text as="p" variant="body">
                        Salin &quot;{scheduleName}&quot; ({dayLabel},{' '}
                        {scheduleTime}) ke lokasi lain. Hari, jam, nama, bahasa,
                        dan catatan ikut tersalin.
                    </Text>
                    <div className="mt-4 space-y-2">
                        <label
                            htmlFor="duplicate-location"
                            className="text-sm font-medium"
                        >
                            Lokasi tujuan
                        </label>
                        <ChoiceSelect
                            id="duplicate-location"
                            value={locationId}
                            onChange={setLocationId}
                            options={locations.map((location) => ({
                                value: String(location.id),
                                label: location.name,
                            }))}
                            placeholder="Pilih lokasi tujuan"
                        />
                        {errors?.location_id ? (
                            <Text as="p" variant="body-sm" color="danger">
                                {errors.location_id}
                            </Text>
                        ) : null}
                    </div>
                </>
            }
            confirmLabel="Duplikasi"
            disabled={isSubmitting || locationId === ''}
            onConfirm={() => onSubmit(Number(locationId))}
        />
    );
}
