<?php

namespace App\Http\Requests\Pelayanan;

use App\Models\Service;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-54
 */
class UpdateServiceFaqRequest extends FormRequest
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
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:5000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.required' => 'Pertanyaan wajib diisi.',
            'question.max' => 'Pertanyaan maksimal :max karakter.',
            'answer.required' => 'Jawaban wajib diisi.',
            'answer.max' => 'Jawaban maksimal :max karakter.',
        ];
    }

    private function service(): Service
    {
        /** @var Service $service */
        $service = $this->route('service');

        return $service;
    }
}
