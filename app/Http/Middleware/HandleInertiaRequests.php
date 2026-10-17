<?php

namespace App\Http\Middleware;

use App\Services\SiteSettingsService;
use Illuminate\Http\Request;
use Inertia\Middleware;

/**
 * The shape every page can rely on. Kept as prose rather than as an enforced
 * return type because parent::share() contributes `errors` as a plain
 * array<string, mixed>, which PHPStan cannot narrow. `resources/js/types/index.ts`
 * holds the TypeScript mirror of this shape.
 *
 * @see docs/DECISIONS.md D-21
 */
class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * Shape:
     *   name: string
     *   auth: array{user: array{id: int, name: string, email: string, email_verified_at: string|null}|null}
     *   locale: string
     *   displayTimezone: string
     *   sidebarOpen: bool
     *   seo: array{appUrl: string, siteName: string|null, title: string|null, description: string|null, ogImage: string|null}
     *   media: array{mimeTypes: list<string>, maxUploadKilobytes: int, maxWidth: int, maxHeight: int}
     *   documentUploads: array{mimeTypes: list<string>, extensions: list<string>, maxUploadKilobytes: int}
     *
     * ARCHITECTURE.md Part C rule 1 forbids handing a raw Eloquent model to the
     * browser, and rule 5 requires shared props to stay small. Everything sent
     * here lands in the page source and is visible to anyone who opens devtools.
     *
     * The authenticated user is the sensitive one. Sending the model itself
     * publishes every column that is not on the #[Hidden] list, which today
     * includes is_active and two timestamp columns that no page renders, plus
     * anything a future relation load decides to attach. The attribute exists as
     * a safety net, not as a contract, so the contract is written out here.
     *
     * `seo` exists because the three seo_default_* settings had no consumer at
     * all: they were configurable in /admin/pengaturan and read by nothing, so
     * editing them changed no page. One shared prop is the shortest path to
     * giving them an effect, and it keeps the reads inside SiteSettingsService
     * per D-23 rather than reaching for the table from a component.
     *
     * `media` exists for the same reason in the opposite direction: config/media.php
     * decides what an upload may be, and the browser has to refuse the same files
     * before the request is made rather than answering with a 422 afterwards. A
     * number written in both places is a number that drifts, so there is one.
     *
     * @see docs/DECISIONS.md D-21, D-26, D-62
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => fn () => $request->user()?->only([
                    'id',
                    'name',
                    'email',
                    'email_verified_at',
                ]),
            ],
            'locale' => fn () => app()->getLocale(),
            'displayTimezone' => fn () => config('app.display_timezone'),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'seo' => fn (): array => $this->seoDefaults(),
            'media' => fn (): array => $this->mediaLimits(),
            'documentUploads' => fn (): array => $this->documentUploadLimits(),
        ];
    }

    /**
     * The site-wide SEO fallbacks, read through the settings service.
     *
     * Returned as null when a setting has never been configured rather than as
     * an empty string, because "no description" and "an empty description" are
     * different things to the Seo component and only the first one should make
     * it omit the tag entirely.
     *
     * appUrl is here so the Seo component never has to read window.location.
     * An Open Graph image has to be an absolute URL or link previews silently
     * show nothing, and seo_default_og_image is a path because the upload widget
     * for it does not exist yet (D-24). Reading window would make the component
     * unsafe to server-render later, which is exactly the change SSR would
     * require.
     *
     * @return array{appUrl: string, siteName: string|null, title: string|null, description: string|null, ogImage: string|null}
     */
    private function seoDefaults(): array
    {
        $settings = app(SiteSettingsService::class);

        $read = static function (string $key) use ($settings): ?string {
            $value = $settings->get($key);

            return is_string($value) && trim($value) !== '' ? $value : null;
        };

        return [
            'appUrl' => rtrim((string) config('app.url'), '/'),
            'siteName' => $read('parish_name'),
            'title' => $read('seo_default_title'),
            'description' => $read('seo_default_description'),
            'ogImage' => $read('seo_default_og_image'),
        ];
    }

    /**
     * What an upload is allowed to be, straight from the media pipeline's config.
     *
     * POST-04 caps the featured image at 3 MB and XC-M3 decides the accepted MIME
     * types, but both live in config/media.php because that is what the server
     * validates against. The browser needs the same three numbers to set the
     * file input's accept attribute, to refuse an oversized file before sending
     * it, and to refuse a decompression bomb's dimensions before decoding it.
     *
     * Sent as a shared prop rather than shared through page props because the
     * answer is the same for every page, admin and public alike, and because a
     * component reading a number it invented for itself is how a 3 MB limit
     * becomes 10 MB in one form and 3 MB in the next.
     *
     * @return array{mimeTypes: list<string>, maxUploadKilobytes: int, maxWidth: int, maxHeight: int}
     */
    private function mediaLimits(): array
    {
        /** @var list<string> $mimeTypes */
        $mimeTypes = array_values((array) config('media.mime_types'));

        return [
            'mimeTypes' => $mimeTypes,
            'maxUploadKilobytes' => (int) config('media.max_upload_kilobytes'),
            'maxWidth' => (int) config('media.max_width'),
            'maxHeight' => (int) config('media.max_height'),
        ];
    }

    /**
     * The document upload limits, straight from config/documents.php.
     *
     * The browser needs the same type list and size ceiling the FormRequest
     * enforces, so a 10 MB file is refused before it is sent rather than after
     * a slow upload.
     *
     * @return array{mimeTypes: list<string>, extensions: list<string>, maxUploadKilobytes: int}
     */
    private function documentUploadLimits(): array
    {
        /** @var array<string, string> $extensions */
        $extensions = (array) config('documents.extensions');

        return [
            'mimeTypes' => array_values((array) config('documents.mime_types')),
            'extensions' => array_values(array_unique($extensions)),
            'maxUploadKilobytes' => (int) config('documents.max_upload_kilobytes'),
        ];
    }
}
