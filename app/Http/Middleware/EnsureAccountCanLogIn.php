<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

/**
 * Refuses to start a session for a deactivated account.
 *
 * Runs as a pipe inside Fortify's login pipeline, positioned after the username
 * is canonicalized and before the credentials are checked. That position
 * matters: a deactivated account is refused before any session is created, so
 * there is never a window where the account is partly authenticated.
 *
 * This layer exists for a clear error message. EnsureAccountIsActive is what
 * actually enforces the rule, because that one also covers sessions that were
 * opened before the account was deactivated.
 *
 * @see docs/DECISIONS.md D-20
 */
class EnsureAccountCanLogIn
{
    /**
     * Handle the login attempt.
     *
     * @param  Closure(Request): (Response)  $next
     *
     * @throws ValidationException
     */
    public function handle(Request $request, Closure $next): Response
    {
        $email = $request->input(Fortify::username());

        if (is_string($email) && $email !== '') {
            $user = User::query()->where('email', $email)->first();

            if ($user !== null && ! $user->is_active) {
                throw ValidationException::withMessages([
                    Fortify::username() => __('auth.inactive'),
                ]);
            }
        }

        return $next($request);
    }
}
