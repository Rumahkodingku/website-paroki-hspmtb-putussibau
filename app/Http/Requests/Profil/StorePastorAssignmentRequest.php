<?php

namespace App\Http\Requests\Profil;

use App\Enums\PastorPosition;
use App\Models\PastorAssignment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @see docs/DECISIONS.md D-52
 */
class StorePastorAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', PastorAssignment::class) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'position' => ['required', Rule::enum(PastorPosition::class)],
            'start_year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1)],
            'end_year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1), 'gte:start_year'],
            'is_current' => ['required', 'boolean'],
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'position.required' => 'Jabatan wajib dipilih.',
            'position.enum' => 'Jabatan tidak valid.',
            'start_year.integer' => 'Tahun mulai harus berupa angka.',
            'end_year.integer' => 'Tahun selesai harus berupa angka.',
            'end_year.gte' => 'Tahun selesai tidak boleh sebelum tahun mulai.',
            'is_current.required' => 'Tentukan apakah penugasan ini yang sedang berjalan.',
            'description.max' => 'Keterangan terlalu panjang.',
        ];
    }
}
