<?php

namespace App\Http\Middleware;

use App\Services\Installation\InstallationState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends traffic to /install until the wizard has finished.
 *
 * The installer is the only safe entry point on a database that has no tables,
 * so any other path would otherwise produce a raw 500. The redirect is a
 * temporary 302 and disappears once the status file says completed; it is
 * gated on the deployment flag so an installed site is never redirected.
 *
 * @see docs/DECISIONS.md D-96
 */
final class RedirectToInstaller
{
    public function __construct(private readonly InstallationState $state) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->state->isCompleted()) {
            return $next($request);
        }

        if (! config('installer.enabled')) {
            if (is_file($this->state->path())) {
                if ($request->is('install') || $request->is('install/*')) {
                    return $next($request);
                }

                if ($request->is('up', 'build/*', 'storage/*', 'favicon*', 'robots.txt', 'sitemap*')) {
                    return $next($request);
                }

                if ($request->expectsJson()) {
                    return response()->json([
                        'status' => 'failed',
                        'error_code' => 'INSTALLER_DISABLED',
                        'message' => 'Instalasi belum selesai dan installer nonaktif.',
                    ], 503);
                }

                abort(503, 'Instalasi belum selesai dan installer nonaktif.');
            }

            return $next($request);
        }

        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        if ($request->is('up', 'build/*', 'storage/*', 'favicon*', 'robots.txt', 'sitemap*')) {
            return $next($request);
        }

        return redirect()->route('install.show');
    }
}
