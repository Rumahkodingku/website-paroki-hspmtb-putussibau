<?php

namespace App\Enums;

/**
 * Where a community is in its life cycle.
 *
 * PRD 7.7 and roadmap 5.2.1 fix these three values, and they are also the column
 * type on `communities.status`, so adding a case here changes the database too
 * rather than leaving a value the column rejects.
 *
 * `forming` and `active` are not the same question as `is_published`: a community
 * may be active and still a draft, and only `active` + `is_published` is public
 * (roadmap 5.2.2). Status is what the community *is*, publication is whether it
 * is shown.
 */
enum CommunityStatus: string
{
    /** A community still being formed. */
    case Forming = 'forming';

    /** An active community. */
    case Active = 'active';

    /** A community that no longer runs. */
    case Inactive = 'inactive';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The interface label, read from the language file rather than hardcoded.
     */
    public function label(): string
    {
        return (string) __('komunitas.status.'.$this->value);
    }
}
