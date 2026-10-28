<?php

namespace App\Http\Middleware;

use App\Services\Backup\BackupHealth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses writes while a destructive restore is running.
 *
 * `php artisan down` would stop the queue worker that is performing the
 * restore, so maintenance is a cache flag checked here instead. The flag has a
 * TTL and the restore job clears it in `finally`.
 */
class BlockWritesDuringRestore
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Cache::get(BackupHealth::RESTORE_FLAG) === true
            && ! in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            abort(503, 'Pemulihan sedang berlangsung. Coba lagi setelah selesai.');
        }

        return $next($request);
    }
}
