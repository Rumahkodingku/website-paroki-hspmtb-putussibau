<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Reads a route-bound model's key for a slug uniqueness check.
 *
 * Kept separate from GeneratesUniqueSlug so actions can reuse the slug helper
 * without pretending to have a route.
 */
trait ResolvesRouteKey
{
    protected function routeKeyId(string ...$names): ?int
    {
        foreach ($names as $name) {
            $route = $this->route($name);

            if ($route instanceof Model) {
                $key = $route->getKey();

                return is_numeric($key) ? (int) $key : null;
            }

            if (is_numeric($route)) {
                return (int) $route;
            }
        }

        return null;
    }
}
