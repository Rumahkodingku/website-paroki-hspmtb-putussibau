<?php

namespace App\Actions\News;

use App\Models\Post;
use Illuminate\Support\Facades\Cache;

/**
 * Counts one view of an article, at most once per session, ignoring bots.
 *
 * PRD D-12 asks for both halves, and both are here rather than in the
 * controller because neither is really the controller's business: the count is a
 * property of the article, and the "have we already counted this" question is a
 * question about the visitor.
 *
 * The user agent arrives as a string rather than as a Request on purpose.
 * ArchitectureTest asserts that App\Services does not depend on the HTTP layer,
 * and the rule is one this class would break if it took the request object — so
 * the controller reads the header and hands over the value. An Action, not a
 * Service, because it writes.
 *
 * The increment is a single UPDATE rather than a read followed by a write, so
 * two tabs open at once cannot lose a count between them.
 *
 * @see docs/PRD.md D-12, POST-08
 */
class RecordPostViewAction
{
    /**
     * Substrings that mark a crawler rather than a reader.
     *
     * A list, not a parser, and it says so: matching well-known bot names cuts
     * the bulk of the inflation and costs nothing. It is not a defence — a
     * determined scraper sets its own header — and the count is not something
     * anybody makes a decision on.
     *
     * @var list<string>
     */
    private const BOT_MARKERS = [
        'bot',
        'crawler',
        'spider',
        'slurp',
        'curl/',
        'wget/',
        'python-requests',
        'headlesschrome',
        'facebookexternalhit',
    ];

    /**
     * @param  string  $userAgent  the visitor's User-Agent header, empty when absent
     * @param  string|null  $ip  the visitor's IP address, used for 24-hour dedup
     */
    public function handle(Post $post, string $userAgent, ?string $ip = null): void
    {
        if ($this->looksLikeABot($userAgent)) {
            return;
        }

        if ($this->alreadyCountedToday($post, $ip, $userAgent)) {
            return;
        }

        $post->increment('view_count');
    }

    private function alreadyCountedToday(Post $post, ?string $ip, string $userAgent): bool
    {
        if ($ip === null || trim($ip) === '') {
            return false;
        }

        $key = 'post-view:'.$post->getKey().':'.hash('sha256', $ip.'|'.trim($userAgent));

        if (Cache::has($key)) {
            return true;
        }

        Cache::put($key, true, now()->addDay());

        return false;
    }

    private function looksLikeABot(string $userAgent): bool
    {
        $haystack = strtolower(trim($userAgent));

        if ($haystack === '') {
            // An empty header is not a reader either. Most real browsers send
            // one, and counting the nameless ones is how a number gets to ten
            // thousand with nobody having opened the page.
            return true;
        }

        foreach (self::BOT_MARKERS as $marker) {
            if (str_contains($haystack, $marker)) {
                return true;
            }
        }

        return false;
    }
}
