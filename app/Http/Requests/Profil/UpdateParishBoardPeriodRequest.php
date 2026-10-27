<?php

namespace App\Http\Requests\Profil;

use App\Models\ParishBoardPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-52
 */
class UpdateParishBoardPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->period()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $sortOrder = $this->input('sort_order');
        $this->merge(['sort_order' => ($sortOrder === null || $sortOrder === '') ? 0 : (int) $sortOrder]);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'start_year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1)],
            'end_year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 5), 'gte:start_year'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
            'is_published' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama periode wajib diisi.',
            'name.max' => 'Nama periode maksimal :max karakter.',
            'start_year.integer' => 'Tahun mulai harus berupa angka.',
            'end_year.integer' => 'Tahun selesai harus berupa angka.',
            'end_year.gte' => 'Tahun selesai tidak boleh sebelum tahun mulai.',
            'is_active.required' => 'Tentukan apakah periode ini aktif.',
            'is_published.required' => 'Tentukan apakah periode ini diterbitkan.',
            'sort_order.integer' => 'Urutan harus berupa angka.',
        ];
    }

    private function period(): ParishBoardPeriod
    {
        /** @var ParishBoardPeriod $period */
        $period = $this->route('period');

        return $period;
    }
}
