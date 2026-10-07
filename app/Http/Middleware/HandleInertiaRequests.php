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
     * @see docs/DECISIONS.md D-21, D-26
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
}
