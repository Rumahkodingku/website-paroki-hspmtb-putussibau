<?php

namespace App\Http\Requests\Pelayanan;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'text' => ['required', 'string', 'max:255'],
            'note' => ['nullable', 'string', 'max:2000'],
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
        ];
    }

    private function service(): Service
    {
        /** @var Service $service */
        $service = $this->route('service');

        return $service;
    }
}
