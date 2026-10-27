<?php

namespace App\Http\Requests\Pelayanan;

use App\Enums\ServiceRequirementCondition;
use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Creating a requirement inside a service.
 *
 * Authorization runs against the parent service (D-54): a requirement has no
 * permission of its own, and `services.update` is the ability the whole tab
 * checks.
 *
 * @see docs/DECISIONS.md D-54
 */
class StoreServiceRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->service()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $condition = $this->input('requirement_condition');

        if (! is_string($condition) || trim($condition) === '') {
            $condition = ServiceRequirementCondition::Required->value;
        }

        $appliesTo = $this->input('applies_to');

        if (is_string($appliesTo)) {
            $appliesTo = trim($appliesTo);

            if ($appliesTo === '') {
                $appliesTo = null;
            }
        }

        $this->merge([
            'requirement_condition' => $condition,
            'applies_to' => $appliesTo,
        ]);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
            'requirement_condition' => ['required', 'string', Rule::enum(ServiceRequirementCondition::class)],
            'applies_to' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'text.required' => 'Teks persyaratan wajib diisi.',
            'text.max' => 'Teks persyaratan maksimal :max karakter.',
            'note.max' => 'Catatan maksimal :max karakter.',
            'requirement_condition.required' => 'Kondisi persyaratan wajib dipilih.',
            'requirement_condition.enum' => 'Kondisi persyaratan tidak dikenali.',
            'applies_to.max' => 'Sasaran persyaratan maksimal :max karakter.',
        ];
    }

    private function service(): Service
    {
        /** @var Service $service */
        $service = $this->route('service');

        return $service;
    }
}
