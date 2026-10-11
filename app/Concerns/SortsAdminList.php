<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Sorting for the four Jadwal Misa list pages.
 *
 * Every list here is server-paginated, so sorting has to be server-side as
 * well. Sorting the fifteen rows the browser already has would be a small lie:
 * the header would say "A–Z" while three quarters of the data stayed put.
 *
 * Three rules, and each of them is there because of a way this goes wrong.
 *
 * 1. **A whitelist, not a column name.** The sort key arrives in a query string
 *    and goes straight into ORDER BY, where it cannot be a bound parameter. A
 *    key outside the list is therefore ignored rather than interpolated, and it
 *    is ignored silently for the same reason a stray `?status=` is: a mangled
 *    link should show a list, not a 422. See AC-MASS-7's treatment of a broken
 *    filter on the public page.
 *
 * 2. **A tie-breaker, always.** Ordering by name alone leaves every row with
 *    the same name in an order MySQL is free to change between queries, so row
 *    16 of page 2 can also be row 1 of page 1 and the table appears to drop
 *    entries. Appending the primary key makes the order total, which is the
 *    only way paginating over it means anything.
 *
 * 3. **The default order survives.** With no sort key the caller's own
 *    ordering is left exactly as it was. Several existing tests assert those
 *    orders, and they are the proof that adding sorting did not quietly
 *    rearrange every list in the module.
 *
 * @see docs/ARCHITECTURE.md Part C section 3
 */
trait SortsAdminList
{
    /**
     * Apply ?sort= and ?direction= to a list query.
     *
     * A controller method rather than a builder macro on purpose: the whitelist
     * is per-list, and a macro would have to be registered with a key and looked
     * up by name, which is the same lookup as an array plus no type safety.
     *
     * The default order lives here rather than in the query chain because of
     * what ORDER BY does with two instructions for one column: MySQL keeps them
     * in the order they were added, so a query that already said
     * `orderBy('name')` and then had `orderBy('name', 'desc')` appended would
     * sort ascending and silently ignore the sort entirely. Deciding here
     * means exactly one order reaches the database.
     *
     * @template TModel of Model
     *
     * @param  array<string, string>  $sortable  request key => qualified column
     * @param  list<array{0: string, 1: 'asc'|'desc'}>  $default
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applyAdminSort(
        Builder $query,
        Request $request,
        array $sortable,
        array $default,
    ): Builder {
        $key = $request->string('sort')->toString();
        $descending = $request->string('direction')->toString() === 'desc'
            ? 'desc'
            : 'asc';

        /** @var list<array{0: string, 1: 'asc'|'desc'}> $orders */
        $orders = array_key_exists($key, $sortable)
            ? [[$sortable[$key], $descending]]
            : $default;

        foreach ($orders as [$column, $direction]) {
            $query->orderBy($column, $direction);
        }

        // The tie-breaker goes on last so it only ever breaks a tie between
        // rows the sort itself left equal. Without it, paginating over an
        // unordered remainder means MySQL may return row 16 of page 2 as row 1
        // of page 1, and the table drops rows while looking complete.
        return $query->orderBy($query->getModel()->getKeyName());
    }
}
