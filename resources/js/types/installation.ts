/**
 * One entry in the installer's seven-step navigation.
 *
 * Shared between the wizard pages (which receive it as a prop) and the
 * installation layout (which renders it on the branding panel), so it lives in
 * the site-wide types rather than in either side.
 */
export type InstallationStep = {
    key: string;
    label: string;
};
