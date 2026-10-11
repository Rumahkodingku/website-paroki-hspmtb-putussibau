<?php

namespace App\Console\Commands;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Console\Command;

/**
 * Moves scheduled articles to published once their time arrives.
 *
 * PRD AC-POST-6 and AC-POST-7. Two acceptance criteria are about this command
 * and both of them are about what happens when it does *not* run, which is why
 * the body of it is one UPDATE and nothing else.
 *
 * A single `UPDATE ... WHERE status = 'scheduled' AND published_at <= now()`
 * rather than a read-then-write loop, for three reasons that all come back to
 * the same thing:
 *
 *   - It is idempotent by construction. Running it twice touches zero rows the
 *     second time, because the rows it changed no longer match `status`. There
 *     is no marker to clear and therefore nothing to get wrong on a re-run
 *     after a crash, a deploy, or a manual retry.
 *   - Two overlapping runs cannot publish an article twice, because publishing
 *     is a change of state and the state only moves in one direction. A
 *     read-then-write loop would find the same row still scheduled in both.
 *   - One statement, one transaction boundary the database owns. The loop
 *     version holds a gap between the read and the write during which an editor
 *     can edit the article — and the edit's `published_at` would then be
 *     overwritten by the stale value the loop already read.
 *
 * What it deliberately does not do is touch `published_at`. The column is the
 * author's chosen moment and the article's position in the list; the scheduler
 * only decides that moment has arrived.
 *
 * Articles whose time has passed but whose status is still `draft` are left
 * alone. "Draft" is an explicit decision not to publish, and an editor who
 * picked a past date while drafting has not asked for anything.
 *
 * @see docs/PRD.md AC-POST-6, AC-POST-7
 */
class PublishScheduledPosts extends Command
{
    protected $signature = 'posts:publish-scheduled';

    protected $description = 'Terbitkan artikel terjadwal yang waktunya sudah tiba';

    public function handle(): int
    {
        $published = Post::query()
            ->where('status', PostStatus::Scheduled->value)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => PostStatus::Published->value]);

        if ($published === 0) {
            $this->components->info('Tidak ada artikel terjadwal yang perlu diterbitkan.');

            return self::SUCCESS;
        }

        // The count is the number of rows the UPDATE matched, which is the
        // number of articles that just became public. Reported so an operator
        // watching a cron run can tell "nothing was due" from "the task did not
        // run", which look identical in a log otherwise.
        $this->components->info("{$published} artikel terjadwal berhasil diterbitkan.");

        return self::SUCCESS;
    }
}
