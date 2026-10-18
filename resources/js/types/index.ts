/*
 * Site-wide types only.
 *
 * A type belongs here when more than one feature needs it. Anything used by a
 * single feature lives next to that feature's components in
 * `features/<name>/types.ts`, so that deleting the feature deletes its contract
 * with it — settings.ts moved out for exactly that reason.
 */
export type * from './auth';
export type * from './document';
export type * from './navigation';
export type * from './pagination';
export type * from './ui';

import type { Auth } from './auth';

/**
 * Site-wide SEO fallbacks, read server-side from `site_settings`.
 *
 * Every field is nullable and null means "never configured", which is what the
 * Seo component needs in order to omit a tag rather than emit an empty one.
 * An empty meta description is worse than none: crawlers and link previews
 * both treat it as a description.
 */
export type SeoDefaults = {
    /** From APP_URL. Lets Seo absolutise paths without touching `window`. */
    appUrl: string;
    siteName: string | null;
    title: string | null;
    description: string | null;
    /** Relative or absolute path; Seo makes it absolute. */
    ogImage: string | null;
};

/**
 * What an upload is allowed to be, read from `config/media.php` on the server.
 *
 * The server validates against that config and so does the browser, which is why
 * it travels as a shared prop rather than as a number written into a component.
 * A limit kept in two places drifts, and it drifts quietly: the browser accepts
 * a file the server then rejects, and the message that comes back talks about
 * the field rather than about the picker that let the file through.
 */
export type MediaLimits = {
    /** From `media.mime_types`. What the file input's `accept` is built from. */
    mimeTypes: string[];
    /** From `media.max_upload_kilobytes`. POST-04's 3 MB, for the article image. */
    maxUploadKilobytes: number;
    /** From `media.max_width`. A decompression-bomb ceiling, checked undecoded. */
    maxWidth: number;
    /** From `media.max_height`. */
    maxHeight: number;
};

/**
 * What a document upload is allowed to be, read from config/documents.php.
 *
 * Separate from MediaLimits on purpose: the media pipeline accepts images only,
 * while documents are PDF/Office/ZIP files on a private disk.
 */
export type DocumentUploadLimits = {
    /** From `documents.mime_types`. */
    mimeTypes: string[];
    /** From the values of `documents.extensions`, e.g. pdf, docx, zip. */
    extensions: string[];
    /** From `documents.max_upload_kilobytes`. PRD DL-01's 10 MB. */
    maxUploadKilobytes: number;
};

/**
 * The parish's public contact details, read from `site_settings` on the server.
 *
 * Every action URL is built server-side (`tel:`, `wa.me`, the validated map
 * URLs), so a component never has to normalise a number or trust a raw value.
 * A null means "not configured", and the component renders nothing rather than
 * an empty link. `homeShowContact` travels with the contact block because it
 * only gates that block, not the /kontak page.
 *
 * @see docs/DECISIONS.md D-67
 */
export type ContactDetails = {
    address: string | null;
    phone: { display: string; tel: string } | null;
    whatsapp: { display: string | null; url: string } | null;
    email: string | null;
    officeHours: string | null;
    mapsEmbedUrl: string | null;
    mapsLink: string | null;
    homeShowContact: boolean;
    social: {
        facebook: string | null;
        instagram: string | null;
        youtube: string | null;
        whatsappChannel: string | null;
    };
};

/**
 * The parish's public identity, read from `site_settings` on the server.
 *
 * What the public navbar and footer render. The Informasi Profil module was
 * removed and identity now lives only in site settings (D-69), so this is the
 * one source for the name, short name, tagline, short description and logo.
 * Every field is nullable: null means "never configured", and the component
 * falls back or renders nothing rather than showing an empty lockup.
 *
 * @see docs/DECISIONS.md D-69
 */
export type IdentityDetails = {
    name: string | null;
    shortName: string | null;
    tagline: string | null;
    shortDescription: string | null;
    /** Resolved URL of the `logo` media setting, or null when unset. */
    logoUrl: string | null;
    logoAlt: string | null;
};

/**
 * Props HandleInertiaRequests shares with every page.
 *
 * Kept in one place so a page that needs a shared prop reaches for the same
 * definition instead of declaring its own version of it.
 *
 * `errors` and `flash` are absent on purpose. Inertia supplies `errors` through
 * its own middleware, and flash is delivered by the `flash` DOM event rather than
 * as a page prop, so neither belongs to this contract.
 *
 * @see docs/DECISIONS.md D-21, D-62, D-67
 */
export type SharedProps = {
    name: string;
    auth: Auth;
    /** App locale, always `id` on the MVP. */
    locale: string;
    /** Storage stays UTC; only this is used to render dates. */
    displayTimezone: string;
    sidebarOpen: boolean;
    seo: SeoDefaults;
    identity: IdentityDetails;
    media: MediaLimits;
    documentUploads: DocumentUploadLimits;
    contact: ContactDetails;
};
