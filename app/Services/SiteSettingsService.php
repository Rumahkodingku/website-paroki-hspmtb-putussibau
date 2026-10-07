<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * The only way application code reads or writes site settings.
 *
 * PRD 9.2 requires settings to be read through a helper or service rather than
 * by querying the table directly, so that caching and type conversion cannot be
 * forgotten at a call site.
 *
 * The key list, types, defaults and cache settings all come from
 * config/site-settings.php. Nothing here hard-codes a key name.
 *
 * @see docs/DECISIONS.md D-23
 */
final class SiteSettingsService
{
    /**
     * Every setting that currently has a row, keyed by name.
     *
     * Returns raw database contents, so a key with no row is absent rather than
     * present with its default. Callers that want a value for one key should use
     * get(); this method is for rendering a list of what has been configured.
     *
     * @return array<string, string|null>
     */
    public function all(): array
    {
        /**
         * Cache key    config('site-settings.cache.key'), one entry for the
         *              whole map.
         * TTL          config('site-settings.cache.ttl'), a safety net.
         * Invalidation forget() on every write, in the same request.
         * Consistency  eventual, which is fine because nothing in the codebase
         *              reads settings inside a transaction that also writes them.
         */
        return Cache::remember(
            config('site-settings.cache.key'),
            config('site-settings.cache.ttl'),
            fn (): array => SiteSetting::query()->pluck('value', 'key')->all(),
        );
    }

    /**
     * Read one setting, converted to its configured type.
     *
     * Falls back to the configured default when no row exists, then to the
     * caller's default, then to null.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        if (! array_key_exists($key, $values)) {
            $configured = (array) config('site-settings.defaults');

            return array_key_exists($key, $configured) ? $configured[$key] : $default;
        }

        return $this->cast($key, $values[$key]);
    }

    /**
     * Read one group, already converted.
     *
     * @return array<string, mixed>
     */
    public function getGroup(string $group): array
    {
        $keys = (array) config("site-settings.groups.{$group}", []);

        return collect($keys)
            ->mapWithKeys(fn (string $key): array => [$key => $this->get($key)])
            ->all();
    }

    /**
     * Write one setting.
     *
     * The group is taken from the configuration, never from the caller, so a
     * key cannot end up filed under the wrong group by a bad request payload.
     */
    public function set(string $key, mixed $value): void
    {
        $this->setMany([$key => $value]);
    }

    /**
     * Write several settings at once.
     *
     * Unknown keys are dropped rather than written, and the whole batch runs in
     * one transaction so a rejected value cannot leave half the form saved.
     *
     * @param  array<string, mixed>  $values
     */
    public function setMany(array $values): void
    {
        $known = $this->knownKeys();

        $writable = array_intersect_key($values, array_flip($known));

        if ($writable === []) {
            return;
        }

        $groups = $this->groupByKey();

        DB::transaction(function () use ($writable, $groups): void {
            foreach ($writable as $key => $value) {
                SiteSetting::query()->updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $this->toStorage($value),
                        'group' => $groups[$key] ?? null,
                    ],
                );
            }
        });

        $this->forget();
    }

    /**
     * Drop the cached map so the next read comes from the database.
     */
    public function forget(): void
    {
        Cache::forget(config('site-settings.cache.key'));
    }

    /**
     * Every key that exists in the configuration, flattened.
     *
     * @return list<string>
     */
    public function knownKeys(): array
    {
        return array_values(array_unique(
            array_merge(...array_values($this->knownKeysByGroup()))
        ));
    }

    /**
     * @return array<string, list<string>>
     */
    private function knownKeysByGroup(): array
    {
        return (array) config('site-settings.groups');
    }

    /**
     * Map every key to the group it belongs to.
     *
     * Built by walking the groups rather than by flipping the array, so a key
     * that was accidentally listed under two groups resolves the same way
     * everywhere instead of depending on key order. The test suite asserts the
     * groups do not overlap in the first place.
     *
     * @return array<string, string>
     */
    private function groupByKey(): array
    {
        $map = [];

        foreach ($this->knownKeysByGroup() as $group => $keys) {
            foreach ($keys as $key) {
                $map[$key] = $group;
            }
        }

        return $map;
    }

    /**
     * Convert a stored string into the type the configuration declares.
     */
    private function cast(string $key, ?string $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match (config("site-settings.types.{$key}")) {
            'integer' => (int) $value,
            'boolean' => filter_var($value, FILTER_VALIDATE_BOOL),
            default => $value,
        };
    }

    /**
     * Convert an incoming value into the string the LONGTEXT column stores.
     */
    private function toStorage(mixed $value): ?string
    {
        return match (true) {
            $value === null => null,
            is_bool($value) => $value ? '1' : '0',
            default => (string) $value,
        };
    }
}
