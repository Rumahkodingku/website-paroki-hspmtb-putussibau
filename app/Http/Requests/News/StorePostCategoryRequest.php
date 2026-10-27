<?php

namespace App\Http\Requests\News;

use App\Concerns\PostCategoryValidationRules;
use App\Models\PostCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates creating an article category.
 *
 * Authorization lives here and nowhere else, per ARCHITECTURE.md Part A
 * section 8: this route has a form request, so the form request is the single
 * place the check happens.
 *
 * @see docs/DECISIONS.md D-43
 */
class StorePostCategoryRequest extends FormRequest
{
    use PostCategoryValidationRules;

    protected function prepareForValidation(): void
    {
        $this->preparePostCategoryInput();
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', PostCategory::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->postCategoryRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->postCategoryMessages();
    }

    /**
     * The validated attributes, ready for PostCategory::create().
     *
     * @return array<string, mixed>
     */
    public function postCategoryPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $this->postCategoryAttributes($validated);
    }
}
