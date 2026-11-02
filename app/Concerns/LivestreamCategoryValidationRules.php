<?php

namespace App\Concerns;

use App\Models\LivestreamCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

trait LivestreamCategoryValidationRules
{
    use GeneratesUniqueSlug;
    use ResolvesRouteKey;

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function livestreamCategoryRules(?LivestreamCategory $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:120',
                Rule::unique('livestream_categories', 'slug')->ignore($ignore?->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function livestreamCategoryMessages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal :max karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'slug.unique' => 'Slug sudah dipakai kategori livestreaming lain.',
            'description.max' => 'Deskripsi maksimal :max karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function livestreamCategoryAttributes(): array
    {
        return [
            'name' => 'nama kategori',
            'slug' => 'slug',
            'description' => 'deskripsi',
        ];
    }

    protected function prepareLivestreamCategoryInput(): void
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
            $ignoreId = $this->routeKeyId('livestreamCategory', 'livestream_category');
            $input['slug'] = $this->resolveUniqueSlug(
                LivestreamCategory::class,
                null,
                (string) $this->input('name'),
                is_numeric($ignoreId) ? (int) $ignoreId : null,
                true,
            );
        } else {
            $input['slug'] = trim((string) $input['slug']);
        }

        $input['is_active'] = (bool) filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        $sortOrder = $this->input('sort_order');
        $input['sort_order'] = ($sortOrder === null || $sortOrder === '') ? 0 : (int) $sortOrder;

        $this->merge($input);
    }
}
