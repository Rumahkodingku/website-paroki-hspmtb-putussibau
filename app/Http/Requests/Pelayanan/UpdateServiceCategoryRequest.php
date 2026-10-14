<?php

namespace App\Http\Requests\Pelayanan;

use App\Concerns\ServiceCategoryValidationRules;
use App\Models\ServiceCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-54
 */
class UpdateServiceCategoryRequest extends FormRequest
{
    use ServiceCategoryValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->category()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCategoryInput();
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->categoryRules($this->category());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->categoryMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->categoryAttributes();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [$this->categoryBusinessRules($this->category())];
    }

    private function category(): ServiceCategory
    {
        /** @var ServiceCategory $category */
        $category = $this->route('service_category');

        return $category;
    }
}
