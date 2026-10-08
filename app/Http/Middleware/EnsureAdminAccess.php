<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * Requires the admin.access permission to enter the admin area.
 *
 * PRD AUTH-R1: every /admin/* route needs authentication, an active account,
 * and authorization. On the MVP the only role holding admin.access is
 * super_admin (AUTH-R5).
 *
 * This checks a permission rather than the role name on purpose. AUTH-R3 makes
 * Spatie the single source of truth for authorization, and Phase 01 13 warns
 * against scattering `if ($user->role === ...)` through controllers. Because
 * AppServiceProvider grants super_admin every ability through Gate::before,
 * Gate::authorize() is also what makes the grant apply here.
 *
 * @see docs/DECISIONS.md D-19
 */
class EnsureAdminAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Gate::authorize('admin.access');

        return $next($request);
    }
}
