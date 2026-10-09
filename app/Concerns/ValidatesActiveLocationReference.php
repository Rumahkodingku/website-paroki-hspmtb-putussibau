<?php

namespace App\Concerns;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * A rule for "this record must exist, be of this type, and be active".
 *
 * Split out on its own because three requests need it and none of them are about
 * a location's own fields: the two schedule requests, the special-mass request
 * and the notice request. Folding it into LocationValidationRules would make
 * those three inherit a coordinate-pair check they have no coordinates for, and
 * would put two `withValidator` hooks in one class.
 *
 * The active condition is MASS-03, and it is not cosmetic. Deactivating a
 * location is how a parish retires one, and a schedule pointing at a retired
 * location would never appear on the public page — so accepting one is how a
 * schedule gets written that nobody can see.
 */
trait ValidatesActiveLocationReference
{
    /**
     * @return array<int, ValidationRule|array<mixed>|string>
     */
    protected function activeLocationRule(): array
    {
        return [
            'required',
            'integer',
            Rule::exists('locations', 'id')->where('is_active', true),
        ];
    }
}
