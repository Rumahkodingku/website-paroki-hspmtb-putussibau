<?php

namespace App\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait GeneratesUniqueSlug
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

    /**
     * Build a slug that is not already in use, keeping a typed slug as-is.
     *
     * @param  class-string  $modelClass
     */
    protected function resolveUniqueSlug(
        string $modelClass,
        mixed $given,
        string $source,
        ?int $ignoreId = null,
        bool $withTrashed = false,
    ): string {
        if (is_string($given) && trim($given) !== '') {
            return trim($given);
        }

        $base = Str::slug($source);
        if ($base === '') {
            $base = 'item';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->slugIsTaken($modelClass, $slug, $ignoreId, $withTrashed)) {
            if ($suffix > 100) {
                $slug = $base.'-'.Str::random(6);

                break;
            }

            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * @param  class-string  $modelClass
     */
    private function slugIsTaken(
        string $modelClass,
        string $slug,
        ?int $ignoreId,
        bool $withTrashed,
    ): bool {
        $query = $withTrashed && method_exists($modelClass, 'withTrashed')
            ? $modelClass::withTrashed()
            : $modelClass::query();

        return $query
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
