import type { Auth } from '@/types';

export type SettingsGroupName =
    | 'identitas'
    | 'kontak'
    | 'sosial'
    | 'seo'
    | 'beranda'
    | 'privasi';

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
 * textarea, a URL, a bounded number or rich text.
 *
 * `richtext` is derived server-side from config('site-settings.types'), so a key
 * declared as html gets an editor by being declared. That is deliberate: the
 * same declaration is what makes UpdateSettingsRequest sanitize it.
 */
export type SettingsMeta = {
    labels: Record<string, string>;
    groupLabels: Record<SettingsGroupName, string>;
    multiline: string[];
    richtext: string[];
    hints: Record<string, string>;
    urls: string[];
    numericRanges: Record<string, [number, number]>;
};

export type SettingsProps = {
    groups: SettingsGroups;
    meta: SettingsMeta;
    auth: Auth;
};
