<?php

use App\Http\Middleware\UseInstallerRuntime;
use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

test('installer requests use filesystem drivers', function () {
    config([
        'session.driver' => 'database',
        'cache.default' => 'database',
        'queue.default' => 'database',
    ]);

    $middleware = new UseInstallerRuntime;

    $response = $middleware->handle(
        Request::create('/install/requirements'),
        fn (): Response => response('ok'),
    );

    expect($response->getStatusCode())->toBe(200)
        ->and(config('session.driver'))->toBe('file')
        ->and(config('cache.default'))->toBe('file')
        ->and(config('cache.limiter'))->toBe('file')
        ->and(config('queue.default'))->toBe('sync');
});

test('requests outside the installer keep their configured drivers', function () {
    config([
        'session.driver' => 'database',
        'cache.default' => 'database',
        'queue.default' => 'database',
    ]);

    $middleware = new UseInstallerRuntime;

    $middleware->handle(
        Request::create('/berita'),
        fn (): Response => response('ok'),
    );

    expect(config('session.driver'))->toBe('database')
        ->and(config('cache.default'))->toBe('database')
        ->and(config('queue.default'))->toBe('database');
});

test('installer requests keep the rate limiter and its named limiters', function () {
    // Fortify registers the `login` limiter on the RateLimiter resolved during
    // provider boot. The installer must not drop that instance; AppServiceProvider
    // points the limiter at the file cache before Fortify boots instead.
    config(['cache.default' => 'database', 'cache.limiter' => null]);

    $limiter = app(RateLimiter::class);
    $limiter->for('installer-test', fn () => Limit::perMinute(1));

    $middleware = new UseInstallerRuntime;

    $middleware->handle(
        Request::create('/install/database/test'),
        fn (): Response => response('ok'),
    );

    expect(config('cache.limiter'))->toBe('file')
        ->and(app(RateLimiter::class))->toBe($limiter)
        ->and($limiter->limiter('installer-test'))->not->toBeNull();
});
