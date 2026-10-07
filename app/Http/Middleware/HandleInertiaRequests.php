<?php

namespace App\Http\Middleware;

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
     * @see docs/DECISIONS.md D-21
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
        ];
    }
}
