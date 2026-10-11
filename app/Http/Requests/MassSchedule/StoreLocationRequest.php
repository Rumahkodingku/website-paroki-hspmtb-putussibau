<?php

namespace App\Http\Requests\MassSchedule;

use App\Concerns\LocationValidationRules;
use App\Models\Location;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates creating a location.
 *
 * Authorization lives here and nowhere else: this route has a form request, so
 * ARCHITECTURE.md Part A section 8 puts the single check in authorize().
 */
class StoreLocationRequest extends FormRequest
{
    use LocationValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Location::class);
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
        return $this->locationRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->locationMessages();
    }

    /**
     * The row to create.
     *
     * @return array<string, mixed>
     */
    public function locationPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $this->nullifyEmptyCoordinates($validated);
    }
}
