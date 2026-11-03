<?php

namespace App\Support\Seo;

use Illuminate\Http\Request;

/**
 * One canonical URL for every public page.
 *
 * The canonical is always absolute and never carries a query string, except
 * for the one parameter that changes which page of a list is being read.
 * Filter, search, and sort parameters are deliberately dropped: /berita?q=misa
 * is a search result, not a second article index.
 */
final class CanonicalUrlResolver
{
    public function resolve(Request $request): string
    {
        $path = rtrim($request->getPathInfo(), '/');

        if ($path === '') {
            $path = '/';
        }

        $page = $request->integer('page');

        if ($page > 1 && $request->is('berita', 'agenda')) {
            $path .= '?page='.$page;
        }

        $base = rtrim((string) config('app.url'), '/');

        if ($base === '') {
            $base = $request->getSchemeAndHttpHost();
        }

        return $base.($path === '/' ? '' : '/'.ltrim($path, '/'));
    }
}
