<?php

namespace App\Models;

use Database\Factories\EventCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What kind of activity an agenda item is: Liturgi, Pembinaan, Sosial, Rapat.
 *
 * The colour is the interesting column. It is stored because a calendar needs
 * to say at a glance which kind of thing happened on the 12th, but roadmap 5.3
 * is explicit that colour may not be the only marker — so the name is rendered
 * next to it everywhere, and the hex is never the thing carrying the meaning.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string $color
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read Collection<int, Event> $events
 */
#[Fillable(['name', 'slug', 'color'])]
class EventCategory extends Model
{
    /** @use HasFactory<EventCategoryFactory> */
    use HasFactory;

    /**
     * @return HasMany<Event, $this>
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function colorValue(): ?string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $this->color) === 1
            ? $this->color
            : null;
    }
}
