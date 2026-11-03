<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Response;

/**
 * The crawl policy. The sitemap line is built from APP_URL, so a staging site
 * does not advertise the production host and a production site does not
 * advertise localhost.
 */
class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $base = rtrim((string) config('app.url'), '/');

        if ($base === '') {
            $base = rtrim(url('/'), '/');
        }

        $body = implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /install',
            'Sitemap: '.$base.'/sitemap.xml',
            '',
        ]);

        return response($body, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }
}
