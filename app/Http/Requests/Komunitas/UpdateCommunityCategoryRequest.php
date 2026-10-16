<?php

namespace App\Http\Requests\Komunitas;

use App\Concerns\CommunityCategoryValidationRules;
use App\Models\CommunityCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-55
 */
class UpdateCommunityCategoryRequest extends FormRequest
{
    use CommunityCategoryValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->category()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareCommunityCategoryInput();
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->communityCategoryRules($this->category());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->communityCategoryMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->communityCategoryAttributes();
    }

    private function category(): CommunityCategory
    {
        /** @var CommunityCategory $category */
        $category = $this->route('community_category');

        return $category;
    }
}
