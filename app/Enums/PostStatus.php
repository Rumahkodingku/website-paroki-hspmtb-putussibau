<?php

namespace App\Enums;

/**
 * Where an article is in its life cycle.
 *
 * PRD section 7.4 lists four states and this is also the column type on
 * `posts` (see the create_posts_table migration), so adding a case here changes
 * the database too rather than leaving a value the column refuses.
 *
 * The four are not four points on one line. Draft is what an article is while
 * somebody is writing it, Scheduled is what an article is while a clock runs,
 * Published is what a visitor can read, and Archived is the drawer an article
 * goes into once it is no longer news but still worth keeping. POST-09 says a
 * draft, a scheduled article and an archived one must all answer 404 to a
 * public URL even when the slug is known, which is why the model has a single
 * `published` scope rather than three separate exclusions.
 *
 * @see docs/DECISIONS.md D-43
 */
enum PostStatus: string
{
    /** Being written. Only an admin sees it. */
    case Draft = 'draft';

    /** Waiting for published_at, which PublishScheduledPosts watches. */
    case Scheduled = 'scheduled';

    /** Public, as soon as published_at has passed. */
    case Published = 'published';

    /** No longer news, still kept. Never appears on the public site. */
    case Archived = 'archived';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The interface label, read from the language file rather than hardcoded.
     *
     * A new status therefore cannot reach the admin interface without someone
     * writing down what it is called.
     */
    public function label(): string
    {
        return (string) __('news.statuses.'.$this->value);
    }
}
