<?php

namespace App\Http\Requests\Agenda;

use App\Concerns\EventCategoryValidationRules;
use App\Models\EventCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates creating an activity category.
 *
 * @see docs/DECISIONS.md D-44
 */
class StoreEventCategoryRequest extends FormRequest
{
    use EventCategoryValidationRules;

    protected function prepareForValidation(): void
    {
        $this->prepareEventCategoryInput();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', EventCategory::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->eventCategoryRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->eventCategoryMessages();
    }

    /**
     * @return array<string, mixed>
     */
    public function eventCategoryPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $this->eventCategoryAttributes($validated);
    }
}
