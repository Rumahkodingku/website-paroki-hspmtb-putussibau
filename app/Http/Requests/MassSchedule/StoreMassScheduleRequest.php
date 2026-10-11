<?php

namespace App\Http\Requests\MassSchedule;

use App\Concerns\MassScheduleValidationRules;
use App\Models\MassSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates creating a recurring mass.
 */
class StoreMassScheduleRequest extends FormRequest
{
    use MassScheduleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', MassSchedule::class);
    }

    protected function prepareForValidation(): void
    {
        $this->merge($this->normaliseTimeInput($this->all()));
    }

    public function withValidator(Validator $validator): void
    {
        $this->rejectDuplicateOccurrence($validator);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            ...$this->massScheduleRules(),
            // MASS-06 as a rule, on top of the exists check the shared rules
            // already put on this field.
            'location_id' => [
                ...$this->activeLocationRule(),
                ...$this->uniqueOccurrenceRule(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->massScheduleMessages();
    }

    /**
     * @return array<string, mixed>
     */
    public function massSchedulePayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        $validated['start_time'] = $this->timeForDatabase($this->input('start_time'));

        return $validated;
    }
}
