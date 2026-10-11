<?php

namespace App\Concerns;

use App\Models\PostCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The rules for an article category's own fields, shared by the store and the
 * update request so the two cannot drift apart.
 *
 * The slug rule is the interesting one, and it is deliberately the opposite of
 * LocationValidationRules. That trait adds ->withoutTrashed() because a deleted
 * location releasing its slug is convenient: locations are referenced by id,
 * so two rows holding one slug is harmless. A category is referenced by slug in
 * the public filter (?kategori=renungan), where two rows answering one slug
 * would make the filter silently show both buckets. Categories are never soft
 * deleted, so the case does not arise — but the rule is written as plain unique
 * because the whole module decided slugs are never reused (D-45).
 *
 * @see docs/DECISIONS.md D-43, D-45
 */
trait PostCategoryValidationRules
{
    /**
     * @param  PostCategory|null  $ignore  the record being updated, so it keeps its own slug
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function postCategoryRules(?PostCategory $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            // `alpha_dash:ascii` plus a separate `lowercase`, not
            // `alpha_dash:ascii,lower`. The combined form is accepted by the
            // rule but does not actually enforce the case in this Laravel
            // version — a slug of `Kategori` passed it, which means every
            // public detail route would 404 on a value the form had accepted.
            // Two rules that both do something beats one that looks like it
            // does.
            'slug' => [
                'required',
                'string',
                'alpha_dash:ascii',
                'lowercase',
                'max:180',
                Rule::unique('post_categories', 'slug')->ignore($ignore?->getKey()),
            ],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function postCategoryMessages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'slug.lowercase' => 'Slug hanya boleh berisi huruf kecil.',
            'slug.unique' => 'Slug ini sudah dipakai kategori lain.',
            'sort_order.required' => 'Urutan wajib diisi.',
            'sort_order.integer' => 'Urutan harus berupa bilangan bulat.',
            'sort_order.min' => 'Urutan tidak boleh negatif.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function postCategoryAttributes(array $validated): array
    {
        return $validated;
    }
}
