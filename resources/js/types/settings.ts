import type { Auth } from '@/types';

export type SettingsGroupName =
    | 'identitas'
    | 'kontak'
    | 'sosial'
    | 'seo'
    | 'beranda';

/** Values arrive already converted by SiteSettingsService. */
export type SettingValue = string | number | boolean | null;

/**
 * Groups of settings, keyed by group name then by setting key.
 *
 * Mirrors the `groups` prop of SettingsController. Per ARCHITECTURE.md Part C
 * rule 4 this has to change whenever the PHP payload does.
 *
 * @see docs/DECISIONS.md D-23
 */
export type SettingsGroups = Record<
    SettingsGroupName,
    Record<string, SettingValue>
>;

/**
 * Presentation hints so the form does not need its own copy of which field is a
 * textarea, a URL, or a bounded number.
 */
export type SettingsMeta = {
    labels: Record<string, string>;
    groupLabels: Record<SettingsGroupName, string>;
    multiline: string[];
    urls: string[];
    numericRanges: Record<string, [number, number]>;
};

export type SettingsProps = {
    groups: SettingsGroups;
    meta: SettingsMeta;
    auth: Auth;
};
