<?php

namespace App\Concerns;

use App\Models\ServiceCategory;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The rules and normalization for a service category, shared by its store and
 * update request.
 *
 * The one check that is not just field validation: a category with active
 * services cannot be deactivated, because roadmap 6B-103 says a service in an
 * inactive category must not be published. Refusing the deactivation keeps that
 * invariant true without silently unpublishing a service nobody touched.
 *
 * slug stays unique across soft-deleted rows, matching services (D-54): the
 * unique index is plain, so the validator must not pretend the slot is free.
 *
 * @see docs/DECISIONS.md D-54
 */
trait ServiceCategoryValidationRules
{
    use GeneratesUniqueSlug;

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function categoryRules(?ServiceCategory $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:100',
                Rule::unique('service_categories', 'slug')->ignore($ignore?->getKey()),
            ],
            'description' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function categoryMessages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal :max karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'slug.unique' => 'Slug sudah dipakai kategori lain.',
            'description.max' => 'Deskripsi maksimal :max karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function categoryAttributes(): array
    {
        return [
            'name' => 'nama kategori',
            'slug' => 'slug',
            'description' => 'deskripsi',
        ];
    }

    protected function prepareCategoryInput(): void
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
            $ignoreId = $this->routeKeyId('serviceCategory', 'service_category');
            $input['slug'] = $this->resolveUniqueSlug(
                ServiceCategory::class,
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

    /**
     * @return Closure(Validator): void
     */
    protected function categoryBusinessRules(?ServiceCategory $existing = null): Closure
    {
        return function (Validator $validator) use ($existing): void {
            if ($existing === null) {
                return;
            }

            /** @var array<string, mixed> $data */
            $data = $validator->validated();

            $isActive = (bool) ($data['is_active'] ?? false);

            if ($isActive) {
                return;
            }

            if ($existing->services()->where('is_active', true)->exists()) {
                $validator->errors()->add(
                    'is_active',
                    'Kategori ini masih memiliki layanan aktif. Nonaktifkan layanan tersebut atau pindahkan ke kategori lain terlebih dahulu.'
                );
            }
        };
    }
}
