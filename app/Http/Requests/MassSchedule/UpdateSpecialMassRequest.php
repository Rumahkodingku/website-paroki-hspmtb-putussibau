<?php

namespace App\Http\Requests\MassSchedule;

use App\Models\SpecialMass;

/**
 * Validates changing a one-off mass.
 *
 * Inherits the field rules and the timezone conversion from the store request
 * rather than repeating them: the two must accept exactly the same payloads, and
 * a copy that drifted would make an existing mass unsaveable. The only thing
 * that differs is which ability authorizes it.
 */
class UpdateSpecialMassRequest extends StoreSpecialMassRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('special_mass'));
    }

    /**
     * The bound record, typed for PHPStan.
     */
    public function specialMass(): SpecialMass
    {
        /** @var SpecialMass $specialMass */
        $specialMass = $this->route('special_mass');

        return $specialMass;
    }
}
