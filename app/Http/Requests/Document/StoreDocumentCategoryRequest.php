<?php

namespace App\Http\Requests\Document;

use App\Concerns\DocumentCategoryValidationRules;
use App\Models\DocumentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentCategoryRequest extends FormRequest
{
    use DocumentCategoryValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', DocumentCategory::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareDocumentCategoryInput();
    }

    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->documentCategoryRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->documentCategoryMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->documentCategoryAttributes();
    }
}
