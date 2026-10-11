<?php

namespace App\Http\Requests\News;

use App\Concerns\PostCategoryValidationRules;
use App\Models\PostCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validates updating an article category.
 *
 * The only difference from the store request is the `$ignore`, threaded through
 * so a category keeps its own slug when the admin edits its name. Without it,
 * saving any edit would fail on the row's own unique key.
 *
 * @see docs/DECISIONS.md D-43
 */
class UpdatePostCategoryRequest extends FormRequest
{
    use PostCategoryValidationRules;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->postCategory()) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->postCategoryRules($this->postCategory());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->postCategoryMessages();
    }

    /**
     * @return array<string, mixed>
     */
    public function postCategoryPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $this->postCategoryAttributes($validated);
    }

    /**
     * The bound record, typed for PHPStan.
     *
     * The route parameter is {post_category}, matching the model name rather
     * than the table — `category` would read as if there were one kind.
     */
    private function postCategory(): PostCategory
    {
        /** @var PostCategory $postCategory */
        $postCategory = $this->route('post_category');

        return $postCategory;
    }
}
