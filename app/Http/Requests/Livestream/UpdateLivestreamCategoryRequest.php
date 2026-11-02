<?php

namespace App\Http\Requests\Livestream;

use App\Concerns\LivestreamCategoryValidationRules;
use App\Models\LivestreamCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLivestreamCategoryRequest extends FormRequest
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
        /** @var LivestreamCategory $category */
        $category = $this->route('livestreamCategory');

        return $this->livestreamCategoryRules($category);
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
