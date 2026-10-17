<?php

namespace App\Http\Requests\Document;

use App\Concerns\DocumentValidationRules;
use App\Models\Document;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDocumentRequest extends FormRequest
{
    use DocumentValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Document::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->prepareDocumentInput();
    }

    /**
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    public function rules(): array
    {
        return $this->documentRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->documentMessages();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return $this->documentAttributes();
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [$this->documentBusinessRules()];
    }

    /**
     * @return array<string, mixed>
     */
    public function documentPayload(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        return $this->documentPayloadAttributes($validated);
    }
}
