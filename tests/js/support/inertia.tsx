import { App } from '@inertiajs/react';
import type { Page } from '@inertiajs/core';
import {
    render,
    type RenderOptions,
    type RenderResult,
} from '@testing-library/react';
import type { ReactElement, ReactNode } from 'react';
import type {
    ContactDetails,
    DocumentUploadLimits,
    IdentityDetails,
    MediaLimits,
    SharedProps,
} from '@/types';

/**
 * A real Inertia context for component tests.
 *
 * This exists because Link and usePage both read Inertia's page context, and
 * @inertiajs/react throws from usePage when there is none:
 *
 *   usePage must be used within the Inertia component
 *
 * So render(<ParishNavbar/>) on its own does not fail with a useful message; it
 * fails with one that names Inertia rather than the component under test. The
 * obvious workaround, stubbing out @inertiajs/react, is worse than no test at
 * all: it removes the Link that is the thing being tested and replaces it with
 * an anchor, so the assertions then describe the mock.
 *
 * App is Inertia's own root component, the same one createInertiaApp mounts, so
 * Link, usePage and the head manager all behave as they do in the browser. Only
 * the initial page is supplied, because these tests render rather than
 * navigate - clicking a link here is asserted on its href, not on the resulting
 * request.
 */

const EMPTY_SEO = {
    appUrl: 'http://localhost',
    siteName: null,
    title: null,
    description: null,
    ogImage: null,
} as const;

/**
 * The upload limits HandleInertiaRequests reads from config/media.php.
 *
 * The real values, not a placeholder pair: an uploader that accepts anything in
 * a test and refuses it in the browser is the mismatch these tests exist to
 * catch, and a fake limit would hide it.
 */
const MEDIA_LIMITS: MediaLimits = {
    mimeTypes: ['image/jpeg', 'image/png'],
    maxUploadKilobytes: 3072,
    maxWidth: 8000,
    maxHeight: 8000,
};

const DOCUMENT_UPLOADS: DocumentUploadLimits = {
    mimeTypes: [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/zip',
        'application/x-zip-compressed',
    ],
    extensions: ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip'],
    maxUploadKilobytes: 10240,
};

/**
 * A parish with no contact details configured.
 *
 * This is the honest default for a component test: every action area has to
 * handle the empty state, and a fixture filled with sample phone numbers would
 * make the empty-state assertions test the fixture instead.
 */
const EMPTY_CONTACT: ContactDetails = {
    address: null,
    phone: null,
    whatsapp: null,
    email: null,
    officeHours: null,
    mapsEmbedUrl: null,
    mapsLink: null,
    homeShowContact: true,
    social: {
        facebook: null,
        instagram: null,
        youtube: null,
        whatsappChannel: null,
    },
};

/**
 * A parish with no identity configured.
 *
 * Null across the board, like EMPTY_CONTACT: the components under test have to
 * handle the empty state, and a filled fixture would test the fixture instead.
 */
const EMPTY_IDENTITY: IdentityDetails = {
    name: null,
    shortName: null,
    tagline: null,
    shortDescription: null,
    logoUrl: null,
    logoAlt: null,
};

/** The shared props a public page receives from HandleInertiaRequests. */
export function publicPageProps(
    overrides: Partial<SharedProps> = {},
): SharedProps {
    return {
        name: 'HSPMTB Putussibau',
        auth: { user: null },
        locale: 'id',
        displayTimezone: 'Asia/Pontianak',
        abilities: { 'auditlogs.view': false },
        sidebarOpen: true,
        seo: { ...EMPTY_SEO },
        identity: { ...EMPTY_IDENTITY },
        media: { ...MEDIA_LIMITS },
        documentUploads: { ...DOCUMENT_UPLOADS },
        contact: { ...EMPTY_CONTACT, social: { ...EMPTY_CONTACT.social } },
        ...overrides,
    };
}

/**
 * The page object Inertia hands to usePage.
 *
 * Only a subset of Page is filled in. The rest is Inertia state that a rendering
 * test never reads - lazy-components and matchProps are per-page bookkeeping,
 * scrollProps belongs to infinite scroll - and spelling them out would mean
 * maintaining a shape that changes between minor versions. Leaving them off is
 * why this casts once, with a comment, instead of pretending the object is
 * complete.
 *
 * `errors` is required by the Page type and is supplied because Inertia's
 * middleware always sets it, even when empty.
 */
const page = {
    component: 'test',
    props: publicPageProps(),
    url: '/',
    version: null,
    clearHistory: false,
    encryptedHistory: false,
    resumed: false,
    flash: {},
    errors: {},
    method: 'get',
    rememberedState: {},
} as unknown as Page<SharedProps>;

/**
 * App renders its children from a component it resolved itself, and when it
 * has none it renders nothing at all - which looks exactly like a component that
 * silently renders an empty div. This placeholder gives it something to resolve
 * so the test's own children are what reaches the DOM.
 */
function Placeholder() {
    return null;
}

/**
 * App resolves the page component for itself before it renders anything, so it
 * needs a resolver even when the test supplies its own children directly. A
 * component that throws is deliberate: if a test ever navigates for real, it
 * should fail saying "this helper does not navigate" rather than silently
 * rendering nothing.
 */
function refuseNavigation(): never {
    throw new Error(
        'renderWithInertia does not navigate. Assert on the link href ' +
            'instead; a real navigation belongs in tests/e2e.',
    );
}

/**
 * Render `ui` inside an Inertia app whose current URL is `url`.
 *
 * url matters: the navbar decides which entry is active by comparing against
 * Inertia's page.url, so a test that wants to assert on active state has to say
 * which page it is pretending to be on.
 */
export function renderWithInertia(
    ui: ReactElement,
    {
        url = '/',
        props = {},
        errors = {},
        ...options
    }: {
        url?: string;
        props?: Partial<SharedProps>;
        errors?: Record<string, string>;
    } & Omit<RenderOptions, 'wrapper'> = {},
): RenderResult {
    const resolved = {
        ...page,
        url,
        props: { ...publicPageProps(props), errors },
        errors,
    } as unknown as Page<SharedProps>;

    function Wrapper({ children }: { children: ReactNode }) {
        return (
            <App
                initialPage={resolved}
                initialComponent={Placeholder}
                resolveComponent={() => refuseNavigation}
            >
                {() => children}
            </App>
        );
    }

    return render(ui, { wrapper: Wrapper, ...options });
}
