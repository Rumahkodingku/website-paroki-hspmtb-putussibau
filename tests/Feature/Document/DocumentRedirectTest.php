<?php

use Illuminate\Support\Facades\Route;

it('redirects the old download URLs permanently', function (string $from, string $to) {
    $this->get($from)->assertRedirect($to)->assertStatus(301);
})->with([
    'public index' => ['/download', '/dokumen'],
    'public download' => ['/download/1/unduh', '/dokumen/1/unduh'],
    'admin index' => ['/admin/download', '/admin/dokumen'],
    'admin categories' => ['/admin/kategori-download', '/admin/master-data/kategori-dokumen'],
]);

it('declares no duplicate route names', function () {
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn ($route) => $route->getName())
        ->filter()
        ->all();

    $duplicates = array_keys(array_filter(
        array_count_values($names),
        fn (int $count): bool => $count > 1
    ));

    expect($duplicates)->toBe([]);
});

it('keeps the public and admin document route names apart', function () {
    expect(parse_url(route('dokumen.index'), PHP_URL_PATH))->toBe('/dokumen')
        ->and(parse_url(route('documents.index'), PHP_URL_PATH))->toBe('/admin/dokumen');
});
