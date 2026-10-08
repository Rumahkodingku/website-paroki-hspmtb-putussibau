<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rejects a deactivated account from the admin area.
 *
 * This is the authoritative layer of the is_active check (PRD D-08). It guards
 * the whole /admin/* prefix, so a deactivated account cannot render any admin
 * page, whichever way it got its session.
 *
 * Login itself is also blocked earlier by EnsureAccountCanLogIn, but that is
 * only for a clear error message. If an account is deactivated while somebody is
 * already signed in, only this middleware can catch it.
 *
 * @see docs/DECISIONS.md D-20
 */
class EnsureAccountIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->is_active) {
            return $next($request);
        }

        // The session is no longer usable, so tear it down rather than leaving a
        // half-authenticated user behind.
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors(['email' => __('auth.inactive')]);
    }
}
