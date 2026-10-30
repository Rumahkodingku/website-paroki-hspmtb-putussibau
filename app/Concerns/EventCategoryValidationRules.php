<?php

namespace App\Concerns;

use App\Models\EventCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The rules for an activity category's own fields, shared by the store and the
 * update request so the two cannot drift apart.
 *
 * Two of the four columns differ from a post category and both are here because
 * they are easy to forget: there is no sort_order, because roadmap 5.3 does not
 * ask for one and the agenda list is ordered by date; and there is a color,
 * which has to be a hex rather than any string, because it ends up in a style
 * attribute on the calendar.
 *
 * The colour rule is validation of *shape*, not of design. Nothing here decides
 * whether green is a good choice for a liturgy — that is the parish's call, and
 * roadmap 5.3 is explicit that colour is a hint and never the only marker, so
 * the category name is rendered beside it wherever the colour appears.
 *
 * @see docs/PRD.md 9.7, roadmap section 5.3
 * @see docs/DECISIONS.md D-44
 */
trait EventCategoryValidationRules
{
    use GeneratesUniqueSlug;

    /**
     * @param  EventCategory|null  $ignore  the record being updated, so it keeps its own slug
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function eventCategoryRules(?EventCategory $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'alpha_dash:ascii',
                'lowercase',
                'max:180',
                Rule::unique('event_categories', 'slug')->ignore($ignore?->getKey()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function eventCategoryMessages(): array
    {
        return [
            'name.required' => 'Nama kategori wajib diisi.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'slug.lowercase' => 'Slug hanya boleh berisi huruf kecil.',
            'slug.unique' => 'Slug ini sudah dipakai kategori lain.',
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function eventCategoryAttributes(array $validated): array
    {
        return $validated;
    }

    protected function prepareEventCategoryInput(): void
    {
        $input = [];

        foreach (['name', 'slug'] as $field) {
            $value = $this->input($field);
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        if (($input['slug'] ?? null) === null || $input['slug'] === '') {
            $ignoreId = $this->routeKeyId('eventCategory', 'event_category');
            $input['slug'] = $this->resolveUniqueSlug(
                EventCategory::class,
                null,
                (string) $this->input('name'),
                is_numeric($ignoreId) ? (int) $ignoreId : null,
                true,
            );
        }

        $this->merge($input);
    }
}
