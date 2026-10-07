<?php

namespace App\Providers;

use App\Models\User;
use App\Services\HtmlSanitizer;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\DevCommands;
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
        /**
         * The HTML sanitizer parses its allowlist on construction, and the
         * allowlist is the same on every field, so one instance per request is
         * enough and building one per field is wasted work.
         *
         * @see docs/DECISIONS.md D-25
         */
        $this->app->singleton(HtmlSanitizer::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->registerSchedulerProcess();
    }

    /**
     * Run the scheduler as part of `composer dev`.
     *
     * Laravel's dev command starts serve, queue:listen, pail and vite, and
     * leaves the scheduler out. That is the right default for an application
     * with nothing scheduled, and this application now has something scheduled,
     * so a scheduled task would simply never run during development without
     * this line. The symptom is the expensive kind to debug: the code is right,
     * the schedule is registered, and nothing happens.
     *
     * Guarded by runningInConsole() because DevCommands::artisan() would
     * otherwise register a process for a web request.
     *
     * @see docs/DECISIONS.md D-26
     */
    protected function registerSchedulerProcess(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        DevCommands::artisan('schedule:work', 'scheduler');
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

        $this->configureStrictModels();
    }

    /**
     * Make Eloquent strict about three classes of mistake outside production.
     *
     * shouldBeStrict() turns on exactly three things: it throws when a relation
     * is lazy loaded, when an attribute is silently discarded during mass
     * assignment, and when an attribute that was never selected is read. Each
     * one is a bug that is invisible until it is not: an N+1 that is fast in
     * development, a fillable list that quietly drops a column, a column rename
     * that returns null instead of failing.
     *
     * ARCHITECTURE.md Part A section 6 requires it and PRD 3.3 says "aktifkan
     * pada non-produksi", so that is the scope. The reason is not only
     * obedience to the document: preventLazyLoading() *throws*, so a single
     * forgotten with() in production becomes a 500 in front of a visitor
     * rather than a page that is merely slow.
     *
     * @see docs/DECISIONS.md D-27
     */
    protected function configureStrictModels(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());
    }
}
