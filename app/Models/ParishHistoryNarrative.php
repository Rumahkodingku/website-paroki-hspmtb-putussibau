<?php

namespace App\Models;

use Database\Factories\ParishHistoryNarrativeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The history narrative — a singleton row.
 *
 * The main narrative of /profil/sejarah. It used to live on
 * `parish_profiles.history_description`; the Informasi Profil module was
 * removed and this narrative moved into the Sejarah module (D-69).
 *
 * The timeline items live in `parish_history_items`; this row is only the
 * prose above the timeline.
 *
 * `narrative` is sanitized HTML, rendered through components/rich-text.tsx.
 *
 * @property int $id
 * @property string|null $narrative
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @see docs/DECISIONS.md D-69
 */
#[Fillable(['narrative'])]
class ParishHistoryNarrative extends Model
{
    /** @use HasFactory<ParishHistoryNarrativeFactory> */
    use HasFactory;

    /**
     * The one row, or an empty instance when none exists yet. No write on read.
     */
    public static function current(): self
    {
        return static::query()->firstOrNew([]);
    }
}
