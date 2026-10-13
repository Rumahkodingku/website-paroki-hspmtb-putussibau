<?php

namespace App\Http\Requests\Profil;

use App\Models\ParishBoardPeriod;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-52
 */
class StoreParishBoardPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ParishBoardPeriod::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->periodRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->periodMessages();
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function periodRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'start_year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1)],
            // end_year >= start_year only when both are present; gte handles
            // null start_year by passing (roadmap 6.4).
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
    protected function periodMessages(): array
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
}
