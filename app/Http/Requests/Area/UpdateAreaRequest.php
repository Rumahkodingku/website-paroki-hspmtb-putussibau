<?php

namespace App\Http\Requests\Area;

use App\Concerns\AreaValidationRules;
use App\Models\Area;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-55
 */
class UpdateAreaRequest extends FormRequest
{
    use AreaValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->area()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareAreaInput();
    }

    /**
     * @return array<string, array<int, \Closure|ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->areaRules($this->area());
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->areaMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->areaAttributes();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [$this->areaBusinessRules($this->area())];
    }

    private function area(): Area
    {
        /** @var Area $area */
        $area = $this->route('area');

        return $area;
    }
}
