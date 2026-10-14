<?php

namespace App\Http\Requests\Pelayanan;

use App\Concerns\ServiceValidationRules;
use App\Models\Service;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-54
 */
class StoreServiceRequest extends FormRequest
{
    use ServiceValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Service::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareServiceInput();
        $this->sanitizeHtmlFields(['introduction']);
    }

    /**
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    public function rules(): array
    {
        return $this->serviceRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->serviceMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->serviceAttributes();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [$this->serviceBusinessRules()];
    }
}
