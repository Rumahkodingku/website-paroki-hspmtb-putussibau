import {
    afterEach,
    beforeEach,
    describe,
    expect,
    it,
    vi,
} from 'vite-plus/test';

import {
    MediaUploadError,
    uploadMediaFile,
    waitForMedia,
} from '@/lib/media-client';
import { createMediaUploadAdapter } from '@/lib/media-upload-adapter';
import type { MediaState } from '@/types/news-agenda';

/*
 * The media pipeline's two calls, and the uploadcn adapter built on them.
 *
 * The thing worth protecting is the wait. An upload is queued (XC-M1), so the
 * POST answers immediately with a row whose status is `pending` and whose
 * variants are an empty array. Any code that stops there hands a form an id the
 * server will reject - the rule on media_id accepts `ready` and nothing else -
 * and publishes an image whose files are not on disk yet. So the tests below are
 * mostly about what the caller is *not* told: a row that has not settled is
 * never returned, and a pipeline that reports failure throws rather than
 * resolving.
 *
 * `sleep` is the reason these run at all: the poll waits 1.5s between reads, and
 * a test suite should not spend two minutes learning that.
 */

const FILE = new File(['x'], 'gereja.jpg', { type: 'image/jpeg' });

function media(overrides: Partial<MediaState> = {}): MediaState {
    return {
        id: 7,
        status: 'ready',
        error_message: null,
        alt_text: null,
        width: 1600,
        height: 900,
        variants: [
            {
                name: 'medium',
                width: 960,
                height: 540,
                file_size: 1024,
                url: '/storage/media/variants/7/medium.webp',
            },
        ],
        ...overrides,
    };
}

function json(body: unknown, status = 200): Response {
    return {
        ok: status >= 200 && status < 300,
        status,
        json: async () => body,
    } as Response;
}

/** The URL a fetch call went to, whichever form fetch was given. */
function urlOf(input: RequestInfo | URL): string {
    if (typeof input === 'string') {
        return input;
    }

    return input instanceof URL ? input.href : input.url;
}

/**
 * A fetch that answers the upload with a row and the reads with `states`, one
 * per call, so a poll that gives up early and a poll that waits can be told
 * apart by the same stub.
 */
function stubPipeline(states: MediaState[]) {
    let reads = 0;

    const sent: { url: string; init: RequestInit | undefined }[] = [];

    const fetchMock = vi.fn(
        async (input: RequestInfo | URL, init?: RequestInit) => {
            const url = urlOf(input);

            sent.push({ url, init });

            if (url === '/admin/media') {
                return json({ media: states[0] });
            }

            const index = reads;

            reads += 1;

            return json({ media: states[Math.min(index, states.length - 1)] });
        },
    );

    vi.stubGlobal('fetch', fetchMock);

    return {
        /** Everything the upload request itself carried. */
        upload: () => sent.find((request) => request.url === '/admin/media'),
        /** Every request that was not the upload itself, in order. */
        polls: () => sent.filter((request) => request.url !== '/admin/media'),
    };
}

beforeEach(() => {
    vi.useFakeTimers();
    document.cookie = 'XSRF-TOKEN=token-from-laravel';
});

afterEach(() => {
    vi.useRealTimers();
    vi.unstubAllGlobals();
    vi.restoreAllMocks();
});

describe('uploadMediaFile', () => {
    it('waits through a pending row instead of returning it', async () => {
        const { polls } = stubPipeline([
            media({ status: 'pending', variants: [] }),
            media({ status: 'processing', variants: [] }),
            media(),
        ]);

        const settled = uploadMediaFile(FILE);
        await vi.runAllTimersAsync();

        await expect(settled).resolves.toMatchObject({
            id: 7,
            status: 'ready',
        });

        // Three reads: two that were not ready yet, and the one that was.
        expect(polls()).toHaveLength(3);
    });

    it('sends the csrf header a plain file input gets no help with', async () => {
        const { upload } = stubPipeline([media()]);

        const settled = uploadMediaFile(FILE);
        await vi.runAllTimersAsync();
        await settled;

        const headers = upload()?.init?.headers as Record<string, string>;

        expect(headers['X-XSRF-TOKEN']).toBe('token-from-laravel');
        expect(headers.Accept).toBe('application/json');
    });

    it('carries the alt text and the form field name in the body', async () => {
        const { upload } = stubPipeline([media()]);

        const settled = uploadMediaFile(FILE, {
            altText: 'Gereja dari halaman depan',
            fieldName: 'featured_image',
        });
        await vi.runAllTimersAsync();
        await settled;

        const body = upload()?.init?.body as FormData;

        expect(body.get('alt_text')).toBe('Gereja dari halaman depan');
        expect(body.get('featured_image')).toBe('1');
    });

    it('throws the server message when the pipeline fails', async () => {
        stubPipeline([
            media({ status: 'failed', error_message: 'Berkas rusak.' }),
        ]);

        const settled = uploadMediaFile(FILE);
        const assertion = expect(settled).rejects.toThrow('Berkas rusak.');

        await vi.runAllTimersAsync();
        await assertion;
    });

    it('stops waiting eventually rather than polling until the tab closes', async () => {
        // An upload that is going to fail has failed by now: the job retries
        // three times with a backoff. A poller with no ceiling is a request
        // every 1.5s for as long as the page is open.
        stubPipeline([media({ status: 'processing', variants: [] })]);

        const settled = uploadMediaFile(FILE);
        const assertion = expect(settled).rejects.toThrow(
            'Pemrosesan gambar terlalu lama.',
        );

        await vi.runAllTimersAsync();
        await assertion;
    });

    it('does not report a network failure as something retrying cannot fix', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () => {
                throw new TypeError('Failed to fetch');
            }),
        );

        const assertion = uploadMediaFile(FILE).catch(
            (error: unknown) => error,
        );
        await vi.runAllTimersAsync();

        const error = await assertion;

        expect(error).toBeInstanceOf(MediaUploadError);
        expect((error as MediaUploadError).retryable).toBe(true);
    });

    it('does not report a rejection as something retrying cannot fix', async () => {
        vi.stubGlobal(
            'fetch',
            vi.fn(async () =>
                json({ message: 'Ukuran gambar maksimal 3072 kilobyte.' }, 422),
            ),
        );

        const assertion = uploadMediaFile(FILE).catch(
            (error: unknown) => error,
        );
        await vi.runAllTimersAsync();

        const error = await assertion;

        expect((error as MediaUploadError).message).toBe(
            'Ukuran gambar maksimal 3072 kilobyte.',
        );
        expect((error as MediaUploadError).retryable).toBe(false);
        expect((error as MediaUploadError).status).toBe(422);
    });
});

describe('waitForMedia', () => {
    it('stops when the caller aborts', async () => {
        const { polls } = stubPipeline([media({ status: 'processing' })]);

        const controller = new AbortController();
        const waiting = waitForMedia(7, { signal: controller.signal });

        controller.abort();

        await expect(waiting).rejects.toThrow('Aborted');
        expect(polls()).toHaveLength(0);
    });
});

describe('createMediaUploadAdapter', () => {
    /**
     * The endpoint the shipped provider pointed at. Nothing here routes
     * `/api/upload`, so an uploader wired to it is not a visibly broken uploader:
     * the dropzone, the progress bar and the retry button all work perfectly
     * around a 404.
     */
    it('posts to the media endpoint this application actually has', async () => {
        const { upload } = stubPipeline([media()]);

        const adapter = createMediaUploadAdapter();
        const uploading = adapter.upload({
            file: FILE,
            signal: new AbortController().signal,
        } as never);
        await vi.runAllTimersAsync();
        await uploading;

        expect(upload()?.url).toBe('/admin/media');
    });

    it('resolves with a settled row, because the caller writes it into a form', async () => {
        stubPipeline([media({ status: 'pending', variants: [] }), media()]);

        const adapter = createMediaUploadAdapter();
        const uploading = adapter.upload({
            file: FILE,
            signal: new AbortController().signal,
        } as never);
        await vi.runAllTimersAsync();

        await expect(uploading).resolves.toMatchObject({
            status: 'ready',
            id: 7,
        });
    });

    it('reads the alt text when the file is sent, not when it was built', async () => {
        const { upload } = stubPipeline([media()]);

        // The alt text is typed after the adapter exists. Capturing it eagerly
        // would store an empty string on the media row, which is the field an
        // admin filled in.
        let altText = '';
        const adapter = createMediaUploadAdapter({
            getAltText: () => altText,
        });

        altText = 'Misa Malam Natal';

        const uploading = adapter.upload({
            file: FILE,
            signal: new AbortController().signal,
        } as never);
        await vi.runAllTimersAsync();
        await uploading;

        const body = upload()?.init?.body as FormData;

        expect(body.get('alt_text')).toBe('Misa Malam Natal');
    });
});
