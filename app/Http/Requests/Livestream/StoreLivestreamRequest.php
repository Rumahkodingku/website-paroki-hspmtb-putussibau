<?php

namespace App\Http\Requests\Livestream;

use App\Concerns\LivestreamValidationRules;
use App\Models\Livestream;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-101
 */
class StoreLivestreamRequest extends FormRequest
{
    use LivestreamValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Livestream::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareLivestreamInput();
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->livestreamRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->livestreamMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->livestreamAttributes();
    }
}
