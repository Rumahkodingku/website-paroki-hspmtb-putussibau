<?php

namespace App\Http\Resources\MassSchedule;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\MissingValue;

/**
 * Guards the nested resources these admin resources embed.
 *
 * Every shape here is resolved by hand rather than handed to a resource
 * collection, because a collection serialises as {"data": [...]} and the page
 * component would then have to unwrap an envelope that carries nothing for it.
 * That choice creates this problem:
 *
 *   - whenLoaded() returns MissingValue when the relation was never loaded, and
 *     null when it was loaded and there is nothing there. A notice with no
 *     mass_schedule_id has a loaded massSchedule that is null.
 *
 *   - both of those then reach ->resolve() on a resource whose subject is null
 *     or MissingValue, and reading a property off either is an error rather
 *     than an absent field.
 *
 * So the two cases are separated once, here, and the field is simply absent when
 * the relation is not there — which is what a null relation means to the
 * component reading it.
 */
trait ResolvesNestedResources
{
    /**
     * A nested resource's array, or null when there is nothing to nest.
     *
     * @param  class-string<JsonResource>  $resource
     * @return array<string, mixed>|null
     */
    protected function nested(string $resource, mixed $value): ?array
    {
        if ($value === null || $value instanceof MissingValue) {
            return null;
        }

        return $resource::make($value)->resolve();
    }

    /**
     * The same, for a list.
     *
     * @param  class-string<JsonResource>  $resource
     * @return list<array<string, mixed>>
     */
    protected function nestedCollection(string $resource, mixed $value): array
    {
        if ($value === null || $value instanceof MissingValue) {
            return [];
        }

        return array_values($resource::collection($value)->resolve());
    }
}
