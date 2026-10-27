<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Give every web request a correlation id.
 *
 * An incoming X-Request-ID is trusted only when it has a safe shape; anything
 * else is replaced. The value is used by the audit log and returned in the
 * response so an operator can correlate a report with a log entry.
 */
class AssignRequestId
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestId = $this->incoming($request) ?? (string) Str::ulid()->toBase32();

        $request->attributes->set('request_id', $requestId);

        $response = $next($request);
        $response->headers->set('X-Request-ID', $requestId);

        return $response;
    }

    private function incoming(Request $request): ?string
    {
        $value = trim($request->header('X-Request-ID', ''));

        return preg_match('/^[A-Za-z0-9._-]{8,64}$/', $value) === 1
            ? $value
            : null;
    }
}
