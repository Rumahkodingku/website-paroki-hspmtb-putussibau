import { useForm } from '@inertiajs/react';
import type {
    OfficeHoursWeek,
    SettingsGroups,
    SettingsMeta,
    SettingValue,
} from '../types';

export type SettingsFormValue = string | OfficeHoursWeek;

export function toWire(value: SettingValue): string | OfficeHoursWeek {
    if (value === null || value === undefined) {
        return '';
    }
    if (typeof value === 'boolean') {
        return value ? '1' : '0';
    }
    if (typeof value === 'object') {
        return value;
    }
    return String(value);
}

export function fieldKind(
    key: string,
    meta: SettingsMeta,
    value: SettingValue,
): string {
    if (typeof value === 'boolean') {
        return 'switch';
    }
    if (meta.officeHours.includes(key)) {
        return 'office-hours';
    }
    if (meta.media.includes(key)) {
        return 'media';
    }
    if (meta.richtext.includes(key)) {
        return 'richtext';
    }
    if (meta.multiline.includes(key)) {
        return 'multiline';
    }
    if (meta.numericRanges[key]) {
        return 'number';
    }
    if (meta.urls.includes(key)) {
        return 'url';
    }
    return 'text';
}

export function useSettingsForm(groups: SettingsGroups, meta: SettingsMeta) {
    const groupsInOrder = Object.keys(groups) as (keyof SettingsGroups)[];

    const initial = Object.fromEntries(
        groupsInOrder
            .flatMap((group) => Object.entries(groups[group]))
            .map(([key, value]) => [key, toWire(value)]),
    ) as Record<string, string | OfficeHoursWeek>;

    const form = useForm<{ settings: Record<string, string | OfficeHoursWeek> }>(
        { settings: initial },
    );

    return { groupsInOrder, form, fieldKind };
}