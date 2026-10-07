<?php

namespace App\Providers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The only role on the MVP (PRD D-16, AUTH-R5).
     */
    public const SUPER_ADMIN = 'super_admin';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
    }

    /**
     * Grant the Super Admin role every ability.
     *
     * Phase 01 10.6 only says super_admin must have full access "through
     * Spatie", so the mechanism is recorded in docs/DECISIONS.md D-16. It is a
     * Gate::before rather than attaching the permissions to the role, because
     * permissions added in later phases are then granted automatically instead
     * of being silently forgotten.
     *
     * Two consequences are easy to get wrong:
     *
     * 1. This must return null, never false. Returning false would short-circuit
     *    every policy in the application. Guarded by the test "denies a user
     *    without the super admin role".
     * 2. It only applies to Gate-level checks (can(), Gate::authorize(), policy
     *    methods). A direct call to hasPermissionTo() bypasses the Gate and is
     *    not covered. Authorization code must therefore use can().
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function (User $user, string $ability): ?bool {
            return $user->hasRole(self::SUPER_ADMIN) ?: null;
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
