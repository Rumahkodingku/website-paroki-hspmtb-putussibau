<?php

namespace App\Actions\Komunitas;

use App\Models\Community;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Writes a community.
 *
 * It is an Action rather than two lines in the controller because two things
 * happen together and one of them is derived (ARCHITECTURE.md Part A section 2):
 *
 *   1. it stamps published_at the first time a community is published, and
 *      leaves it alone afterwards (D-55 point 9) — a community keeps the date it
 *      was first shown;
 *
 *   2. it runs the write in one transaction, so a half-saved profile cannot
 *      happen.
 *
 * published_at is not a request field at all: it is derived here, never posted,
 * so a browser cannot set a date the community was not published on.
 *
 * @see docs/DECISIONS.md D-55
 */
class SaveCommunityAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array<string, mixed>  $attributes  the validated payload
     */
    public function handle(Community $community, array $attributes): Community
    {
        return DB::transaction(function () use ($community, $attributes): Community {
            $isNew = ! $community->exists;
            $publishedBefore = $isNew ? false : (bool) $community->is_published;
            $before = $isNew ? [] : $this->audit->snapshot($community, 'communities');

            $attributes['published_at'] = $this->resolvePublishedAt($community, $attributes);

            $community->fill($attributes);
            $community->save();

            if ($isNew) {
                $this->audit->created('communities', $community);
            } else {
                $publishedAfter = (bool) $community->is_published;
                $action = $publishedBefore === $publishedAfter
                    ? 'updated'
                    : ($publishedAfter ? 'published' : 'unpublished');

                $this->audit->updated('communities', $community, $before, $action);
            }

            return $community;
        });
    }

    /**
     * D-55 point 9: set once, on the first publication.
     *
     * Reading published_at from the model *before* fill() is what makes this
     * "once": a re-save while already published sees the date it was given the
     * first time and keeps it, and unpublishing does not clear it.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function resolvePublishedAt(Community $community, array $attributes): mixed
    {
        $willPublish = (bool) ($attributes['is_published'] ?? false);

        if ($willPublish && $community->published_at === null) {
            return now();
        }

        return $community->published_at;
    }
}
