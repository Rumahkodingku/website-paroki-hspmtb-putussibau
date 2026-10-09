<?php

namespace App\Http\Requests\MassSchedule;

use App\Concerns\LocationValidationRules;
use App\Models\Location;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates changing a location.
 *
 * The slug uniqueness check ignores this record, otherwise saving a location
 * without touching its slug fails against itself.
 */
class UpdateLocationRequest extends FormRequest
{
    use LocationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->location());
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normaliseCoordinateInput($this->all()));
    }

    public function withValidator(Validator $validator): void
    {
        $this->rejectHalfFilledCoordinates($validator);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->locationRules($this->location());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->locationMessages();
    }

    /**
     * @return array<string, mixed>
     */
    public function locationPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $this->nullifyEmptyCoordinates($validated);
    }

    /**
     * The bound record, typed.
     *
     * Route model binding guarantees a Location or a 404; this is here so the
     * analyser sees a model rather than a mixed route parameter.
     */
    private function location(): Location
    {
        /** @var Location $location */
        $location = $this->route('location');

        return $location;
    }
}
