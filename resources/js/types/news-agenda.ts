/*
 * Types more than one Berita & Agenda feature needs.
 *
 * ARCHITECTURE.md Part B section 1.1 draws the line at two consumers: a type one
 * feature needs lives in that feature, so deleting the feature deletes its
 * contract; a type several features need lives here. This module has four admin
 * features and two public ones, and these are the handful of shapes all of them
 * read — the rest stay with the feature that owns them.
 *
 * Every field here has a counterpart in a PHP resource or in a FormMeta concern.
 * When one side changes the other has to change in the same commit, because a
 * prop shape is a contract rather than a convenience (ARCHITECTURE.md Part C
 * rule 4).
 */

/**
 * The language file's shape, as PostFormMeta and EventFormMeta send it.
 *
 * `labels` and `hints` are keyed by field name and every lookup is optional on
 * the component side. A field the server knows about and lang/id/ does not
 * should render as the field's own name rather than as a raw translation key —
 * and `Object.create` style fallbacks would hide a missing entry instead of
 * showing it.
 */
export type FormMeta = {
    labels: Record<string, string>;
    hints: Record<string, string>;
    statuses: Record<string, string>;
    /** Derived from PostStatus, so a new status cannot appear without a label. */
    postStatusChoices?: Array<{ value: string; label: string }>;
    /** Derived from EventStatus. */
    eventStatusChoices?: Array<{ value: string; label: string }>;
};

/** A select option built locally rather than read from the server. */
export type Choice = { value: string; label: string };

/**
 * A category or location as a dropdown needs it.
 *
 * `color_value` is only ever a colour already shaped into a CSS value, or null.
 * The hex lives in the database and is validated there; a component must never
 * carry one as a literal, which is what
 * ArchitectureTest::'no component reaches for a brand colour by hex value' is
 * for. See EventCategory::colorValue().
 */
export type Option = {
    id: number;
    name: string;
    slug: string;
    color_value?: string | null;
};

/** What both category list pages read back out of the URL. */
export type CategoryFilters = {
    search: string;
    sort: string;
    direction: string;
};

/**
 * The media row the image picker polls.
 *
 * Mirrors MediaController::show(). The status is the point of the type: XC-M1
 * asks for the processing state to be shown, and an admin who cannot tell
 * "still uploading" from "broken" uploads the file a second time.
 */
export type MediaState = {
    id: number;
    status: 'pending' | 'processing' | 'ready' | 'failed';
    error_message: string | null;
    alt_text: string | null;
    width: number | null;
    height: number | null;
    variants: Array<{
        name: string;
        width: number;
        height: number;
        file_size: number;
        url: string | null;
    }>;
};

/**
 * Card data shared by the Berita list and the home devotion strip.
 *
 * Lives in types/ because two features (Berita and Beranda) read it; keeping it
 * in a feature would make the other feature import across the feature boundary.
 */
export type ArticleCardData = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    image: string | null;
    image_alt: string | null;
    /** Fallback illustration, not a photograph of the parish (D-71). */
    image_is_illustration?: boolean;
    /** Sent only for locally committed illustrations. */
    image_srcset?: string;
    image_sizes?: string;
    category: {
        id: number;
        name: string;
        slug: string;
    } | null;
    published_at: string | null;
};
