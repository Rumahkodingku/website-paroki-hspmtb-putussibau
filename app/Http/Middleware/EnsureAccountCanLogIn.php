<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\LoginRateLimiter;
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
 * Percobaan login terhadap akun nonaktif dicatat ke LoginRateLimiter yang sama
 * agar percobaan berulang dibatasi, sekaligus mencegah enumerasi status akun
 * tanpa verifikasi password (security audit finding
 * auth-inactive-account-oracle-before-password-check).
 *
 * @see docs/DECISIONS.md D-20
 */
class EnsureAccountCanLogIn
{
    public function __construct(private readonly LoginRateLimiter $limiter) {}

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
        $password = $request->input('password');

        if (is_string($email) && $email !== '' && is_string($password) && $password !== '') {
            $user = User::query()->where('email', $email)->first();

            if ($user !== null && ! $user->is_active && Hash::check($password, $user->password)) {
                $this->limiter->increment($request);

                throw ValidationException::withMessages([
                    Fortify::username() => __('auth.inactive'),
                ]);
            }
        }

        return $next($request);
    }
}
