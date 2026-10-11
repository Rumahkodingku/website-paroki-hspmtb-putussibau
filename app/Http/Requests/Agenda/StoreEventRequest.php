<?php

namespace App\Http\Requests\Agenda;

use App\Concerns\EventValidationRules;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validates creating an activity.
 *
 * @see docs/DECISIONS.md D-44
 */
class StoreEventRequest extends FormRequest
{
    use EventValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Event::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->normaliseEventInput();
    }

    public function withValidator(Validator $validator): void
    {
        $this->rejectSpanningAllDayEvent($validator);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->eventRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->eventMessages();
    }

    /**
     * @return array<string, mixed>
     */
    public function eventPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $this->eventAttributes($validated);
    }
}
