<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        /**
         * How the scheduler is actually started is not a schedule entry.
         *
         * In production a cron line calls schedule:run every minute; in
         * development `composer dev` starts schedule:work as one of its five
         * processes. Scheduling schedule:run from inside the schedule would
         * have it invoke itself, so that line is deliberately not here.
         *
         * @see docs/DECISIONS.md D-26
         */
        $schedule->command('queue:prune-failed --hours=168')->daily();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /**
         * Render the Indonesian error page for browser requests.
         *
         * XC-E2 requires 404 and 500 in Bahasa Indonesia inside the public
         * layout, with a link back to the home page. 403 and 419 are here too
         * because this application produces both: a signed-in user without a
         * role gets 403 on /admin, and an expired session gets 419. Leaving them
         * to the framework means an Indonesian administrator meets an English
         * page for a case they will actually hit.
         *
         * The gate is config('app.debug') and not the environment list Inertia's
         * documentation suggests. Both exist to keep the framework's own error
         * page, which is only useful while somebody is debugging; keying off the
         * environment would also switch the page off during the test suite, and
         * a page whose only test is the assertion that it is not being used is
         * not a page that has been verified.
         *
         * The two content checks mirror shouldRenderJsonWhen() above on purpose.
         * A request that accepts both JSON and HTML has to be treated as JSON,
         * because that is what the framework will do with it, and the two gates
         * disagreeing would make the error page depend on registration order.
         * expectsJson() alone is not enough: Laravel 13 dropped expectsHtml(),
         * so acceptsHtml() carries the browser half.
         *
         * Only the status crosses over. The exception never reaches the
         * response, which is what keeps a stack trace out of a production 500.
         *
         * @see docs/DECISIONS.md D-26
         */
        $exceptions->respond(function (SymfonyResponse $response, Throwable $exception, Request $request) {
            if (config('app.debug') || $request->is('api/*') || $request->expectsJson() || ! $request->acceptsHtml()) {
                return $response;
            }

            $status = $response->getStatusCode();

            if (! in_array($status, [403, 404, 419, 500, 503], true)) {
                return $response;
            }

            return Inertia::render('public/error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })->create();
