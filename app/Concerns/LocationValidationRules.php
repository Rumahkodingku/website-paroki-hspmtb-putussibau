<?php

namespace App\Concerns;

use App\Enums\LocationType;
use App\Models\Location;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The rules for a location's own fields, shared by the store and the update
 * request so the two cannot drift apart.
 *
 * The interesting one is the coordinate pair. latitude and longitude are two
 * nullable columns and filling one without the other produces a map link
 * pointing at open ocean: Location::mapsUrl() requires both, so a half-filled
 * form silently loses its "Buka di Google Maps" button instead of being told
 * why.
 *
 * That check is a method the request calls rather than a `withValidator` hook,
 * for two reasons. A rule attached to either field would only run when that field
 * was present, and the case that matters is one present and the other absent;
 * and a hook here would collide with the one the schedule rules need.
 *
 * @see docs/PRD.md 7.3, XC-S2
 */
trait LocationValidationRules
{
    use ValidatesActiveLocationReference;

    /**
     * @param  Location|null  $ignore  the record being updated, so it keeps its own slug
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function locationRules(?Location $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            // alpha_dash because the slug ends up in a shareable query string.
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:180',
                Rule::unique('locations', 'slug')->ignore($ignore?->getKey()),
            ],
            'type' => ['required', Rule::enum(LocationType::class)],
            'address' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // url:http,https rather than plain url: a link target is not free
            // text, and this one is rendered as an anchor on the public page.
            'maps_url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function locationMessages(): array
    {
        return [
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'type.enum' => 'Jenis lokasi tidak valid.',
            'latitude.between' => 'Lintang harus antara -90 dan 90.',
            'longitude.between' => 'Bujur harus antara -180 dan 180.',
            'maps_url.url' => 'URL Google Maps harus berupa tautan http atau https.',
        ];
    }

    /**
     * Register the check that a coordinate pair is all-or-nothing.
     *
     * The callback is registered rather than the error added inline, and that is
     * load-bearing: Validator::passes() starts with `$this->messages = new
     * MessageBag`, so anything written into the bag while the validator is still
     * being built is wiped before the rules run. A check that adds an error
     * directly here appears to work and silently never fires.
     */
    protected function rejectHalfFilledCoordinates(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var array<string, mixed> $input */
            $input = $this->all();

            $hasLatitude = $this->hasCoordinate($input['latitude'] ?? null);
            $hasLongitude = $this->hasCoordinate($input['longitude'] ?? null);

            if ($hasLatitude xor $hasLongitude) {
                $validator->errors()->add(
                    'longitude',
                    __('Koordinat lintang dan bujur harus diisi keduanya atau dikosongkan keduanya.')
                );
            }
        });
    }

    /**
     * Whether a posted coordinate counts as present.
     *
     * An empty string from an untouched number input is absent, not a value:
     * treating it as present would reject every form that fills only latitude.
     *
     * Named hasCoordinate rather than filled because this trait is used by a
     * FormRequest, which extends Request — and Request already has a public
     * filled(). Reusing that name narrows a public method to private and the
     * class stops loading.
     */
    private function hasCoordinate(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }

    /**
     * Decimal commas are what an Indonesian keyboard and faker id_ID both
     * produce, so "-2,1234567" becomes "-2.1234567" before it reaches the
     * numeric rule. A rule can reject but never rewrite, so this has to happen
     * first.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    protected function normaliseCoordinateInput(array $input): array
    {
        foreach (['latitude', 'longitude'] as $key) {
            $value = $input[$key] ?? null;

            if (is_string($value)) {
                $input[$key] = str_replace(',', '.', trim($value));
            }
        }

        return $input;
    }

    /**
     * Turn an empty coordinate field into an actual null.
     *
     * '' would otherwise be stored as a zero coordinate, which maps to a point
     * in the Atlantic.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function nullifyEmptyCoordinates(array $validated): array
    {
        foreach (['latitude', 'longitude'] as $key) {
            if (($validated[$key] ?? null) === '') {
                $validated[$key] = null;
            }
        }

        return $validated;
    }
}
