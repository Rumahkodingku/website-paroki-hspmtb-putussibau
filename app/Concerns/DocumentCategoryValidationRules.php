<?php

namespace App\Concerns;

use App\Models\DocumentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait DocumentCategoryValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function documentCategoryRules(?DocumentCategory $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'nullable',
                'string',
                'alpha_dash',
                'max:100',
                Rule::unique('document_categories', 'slug')->ignore($ignore?->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function documentCategoryMessages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal :max karakter.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'slug.unique' => 'Slug sudah dipakai kategori lain.',
            'description.max' => 'Deskripsi maksimal :max karakter.',
            'sort_order.integer' => 'Urutan harus berupa angka.',
            'sort_order.min' => 'Urutan tidak boleh negatif.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function documentCategoryAttributes(): array
    {
        return [
            'name' => 'nama kategori',
            'slug' => 'slug',
            'description' => 'deskripsi',
            'sort_order' => 'urutan',
            'is_active' => 'status aktif',
        ];
    }

    protected function prepareDocumentCategoryInput(): void
    {
        $input = [];

        foreach (['name', 'slug', 'description'] as $field) {
            $value = $this->input($field);
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        if (($input['description'] ?? null) === '') {
            $input['description'] = null;
        }

        if (($input['slug'] ?? null) === null || $input['slug'] === '') {
            $input['slug'] = Str::slug((string) $this->input('name'));
        }

        $input['sort_order'] = max(0, (int) $this->input('sort_order', 0));
        $input['is_active'] = (bool) filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN);

        $this->merge($input);
    }
}
