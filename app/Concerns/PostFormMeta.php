<?php

namespace App\Concerns;

use App\Enums\PostStatus;

/**
 * The labels and choices the Berita admin screens share.
 *
 * Indonesian interface copy lives in lang/id/news.php and is sent to React
 * rather than typed a second time in TypeScript. MassScheduleFormMeta made that
 * call and the reason has not changed: two copies of a label drift, and the one
 * that drifts is usually the one nobody re-reads.
 *
 * The statuses are derived from the enum rather than listed, which is the part
 * that matters. A PostStatus case cannot reach the database without a label for
 * it, because the column type is the enum's own values() and the dropdown is
 * built from the same cases.
 *
 * @see docs/DECISIONS.md D-43
 */
trait PostFormMeta
{
    /**
     * Everything the article forms need that is not a record.
     *
     * @return array<string, mixed>
     */
    protected function postFormMeta(): array
    {
        return [
            'labels' => (array) __('news.labels'),
            'hints' => (array) __('news.hints'),
            'statuses' => (array) __('news.post_statuses'),
            'postStatusChoices' => array_map(
                static fn (PostStatus $status): array => [
                    'value' => $status->value,
                    'label' => $status->label(),
                ],
                PostStatus::cases(),
            ),
        ];
    }
}
