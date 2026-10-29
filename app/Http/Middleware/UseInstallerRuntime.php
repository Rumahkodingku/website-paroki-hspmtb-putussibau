<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keeps the installer usable on a database that has no tables yet.
 *
 * The application normally stores sessions, cache and queue jobs in MySQL.
 * Before the first migration none of those tables exist, so any installer
 * request would fail before reaching the installer itself. For `/install*`
 * only, this middleware switches the three stores that a page or form needs to
 * drivers that work on the filesystem. Everything outside the installer keeps
 * the deployment's own configuration.
 *
 * It is registered as global prepend middleware because StartSession runs in
 * the `web` group: a route middleware would be too late.
 *
 * @see docs/DECISIONS.md D-94
 */
final class UseInstallerRuntime
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->is('install') || $request->is('install/*')) {
            /**
             * Array drivers already work without a database, and swapping away
             * from them would discard the session the test suite seeded. Every
             * persistent driver is replaced.
             *
             * cache.limiter is set as well: the rate limiter resolves its own
             * repository from that key, and on an uninstalled app the default
             * database store has no table to count attempts in.
             */
            $cacheDriver = config('cache.default') === 'array' ? 'array' : 'file';

            config([
                'session.driver' => config('session.driver') === 'array' ? 'array' : 'file',
                'cache.default' => $cacheDriver,
                'cache.limiter' => $cacheDriver,
                'queue.default' => 'sync',
            ]);
        }

        return $next($request);
    }
}
