<?php

namespace App\Http\Requests\MassSchedule;

use App\Concerns\MassScheduleValidationRules;
use App\Models\MassSchedule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates changing a recurring mass.
 *
 * The duplicate check ignores this record, so re-saving a schedule without
 * changing its day, time or location is not reported as a duplicate of itself.
 *
 * MASS-04 also means an inactive schedule still occupies its slot: the unique
 * index covers inactive rows too, because the record stays in the table. A
 * schedule that was switched off can therefore not be silently replaced by a new
 * one at the same place — it has to be reactivated or edited.
 */
class UpdateMassScheduleRequest extends FormRequest
{
    use MassScheduleValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->massSchedule());
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
            'location_id' => [
                ...$this->activeLocationRule(),
                ...$this->uniqueOccurrenceRule($this->massSchedule()),
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

    /**
     * The bound record, typed for PHPStan.
     */
    private function massSchedule(): MassSchedule
    {
        /** @var MassSchedule $massSchedule */
        $massSchedule = $this->route('mass_schedule');

        return $massSchedule;
    }
}
