<?php

namespace App\Http\Requests\Livestream;

use App\Concerns\LivestreamCategoryValidationRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreLivestreamCategoryRequest extends FormRequest
{
    use LivestreamCategoryValidationRules;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareLivestreamCategoryInput();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->livestreamCategoryRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->livestreamCategoryMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->livestreamCategoryAttributes();
    }
}
