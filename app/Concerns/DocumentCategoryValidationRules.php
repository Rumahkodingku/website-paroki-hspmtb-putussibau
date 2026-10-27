<?php

namespace App\Concerns;

use App\Models\DocumentCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait DocumentCategoryValidationRules
{
    use GeneratesUniqueSlug;

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
            $ignoreId = $this->routeKeyId('documentCategory', 'document_category');
            $input['slug'] = $this->resolveUniqueSlug(
                DocumentCategory::class,
                null,
                (string) $this->input('name'),
                is_numeric($ignoreId) ? (int) $ignoreId : null,
                true,
            );
        } else {
            $input['slug'] = trim((string) $input['slug']);
        }

        $sortOrder = $this->input('sort_order');
        $input['sort_order'] = ($sortOrder === null || $sortOrder === '') ? 0 : (int) $sortOrder;
        $input['is_active'] = (bool) filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN);

        $this->merge($input);
    }
}
