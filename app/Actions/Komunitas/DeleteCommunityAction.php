<?php

namespace App\Actions\Komunitas;

use App\Models\Community;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Removes a community.
 *
 * Soft delete, so the row and its slug survive and the slug is not reused
 * (D-55 point 7).
 *
 * It never touches posts or events: there is no relation to them (D-43), so
 * there is nothing to clean and nothing to accidentally delete. It no longer
 * touches areas either — the community_area pivot was removed in D-57.
 *
 * @see docs/DECISIONS.md D-55, D-57
 */
class DeleteCommunityAction
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function handle(Community $community): void
    {
        DB::transaction(function () use ($community): void {
            $before = $this->audit->snapshot($community, 'communities');
            $community->delete();
            $this->audit->deleted('communities', $community, $before);
        });
    }
}
