import { useState } from 'react';
import { ConfirmDialog } from '@/components/confirm-dialog';
import { Text } from '@/components/text';
import { ChoiceSelect } from './form-fields';
import type { LocationOption } from '../types';

/*
 * Copying a schedule to another location.
 *
 * PRD 7.3 lists duplication as an admin capability because a parish with a
 * stasi and two chapels enters the same Sunday mass four times and has four
 * places to forget to update one of them.
 *
 * The dialog asks only for the destination. Day, hour, name, language and notes
 * come from the source — offering them here would mean the form could describe
 * something other than what it is about to copy.
 *
 * Both failure modes come back from the server rather than being pre-empted
 * here: a destination with no room for this day and time, and a destination the
 * parish has switched off. The second matters more than it looks — a copy
 * written onto a deactivated location is invisible from the moment it exists.
 */

type Props = {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    locations: LocationOption[];
    /** The schedule being copied, for the confirmation text. */
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
                            <Text as="p" variant="caption" color="danger">
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
