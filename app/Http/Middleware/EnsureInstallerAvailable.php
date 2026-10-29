<?php

namespace App\Http\Middleware;

use App\Services\Installation\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses the installer when it is disabled or already finished.
 *
 * The configuration flag is what makes the installer a deployment tool rather
 * than a permanent public feature. `completed` is terminal: the only routes
 * that stay reachable are the progress poll and the one-time success page,
 * both of which refuse to show anything without a completed state.
 *
 * @see docs/DECISIONS.md D-90, D-91
 */
final class EnsureInstallerAvailable
{
    public function __construct(private readonly InstallationState $state) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('installer.enabled')) {
            abort(404);
        }

        if ($this->state->isCompleted() && ! $request->routeIs('install.progress', 'install.complete')) {
            if ($request->expectsJson()) {
                return response()->json(['status' => InstallationState::COMPLETED], 409);
            }

            if (! $request->isMethod('GET')) {
                abort(409, 'Instalasi sudah selesai.');
            }

            return redirect()->route('login');
        }

        return $next($request);
    }
}
