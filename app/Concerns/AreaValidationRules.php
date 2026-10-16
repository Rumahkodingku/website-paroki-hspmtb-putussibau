<?php

namespace App\Concerns;

use App\Enums\AreaType;
use App\Models\Area;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The rules and normalization for an area, shared by its store and update
 * request.
 *
 * The field rules are ordinary; the cross-field rules are the whole point. PRD
 * COM-02 fixes a two-level hierarchy and D-57 relaxes the parent rule: `wilayah`
 * and `stasi` are top-level, and a `lingkungan` may either be top-level too (this
 * parish has no wilayah, so its centre lingkungan are) or sit under a `wilayah`.
 * When a parent is given, it must be an active wilayah. That rule cannot be
 * expressed as a field rule — whether `parent_id` is legal depends on the *other*
 * area's type — so it runs in after(), where the whole payload and the existing
 * row are both visible.
 *
 * @see docs/DECISIONS.md D-55, D-57
 */
trait AreaValidationRules
{
    /**
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    protected function areaRules(?Area $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:180',
                Rule::unique('areas', 'slug')->ignore($ignore?->getKey()),
            ],
            'type' => ['required', Rule::enum(AreaType::class)],
            'parent_id' => ['nullable', 'integer', Rule::exists('areas', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],

            /*
             * Where the place is (D-61). Every rule is copied from
             * LocationValidationRules, so the two modules that both store a
             * coordinate cannot drift apart on what a valid one looks like.
             */
            'address' => ['nullable', 'string', 'max:2000'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            // url:http,https rather than plain url: this one is rendered as an
            // anchor on the public page, not printed as text.
            'maps_url' => ['nullable', 'string', 'url:http,https', 'max:2048'],
            'leader_name' => ['nullable', 'string', 'max:150'],
            'contact_phone' => [
                'nullable',
                'string',
                'max:50',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (is_string($value) && trim($value) !== '' && ! PhoneNumber::isValid($value)) {
                        $fail('Nomor telepon tidak dikenali. Gunakan angka, boleh dengan awalan 0 atau +62.');
                    }
                },
            ],
            'show_contact' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function areaMessages(): array
    {
        return [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal :max karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'slug.unique' => 'Slug sudah dipakai wilayah/lingkungan lain.',
            'type.required' => 'Tipe area wajib dipilih.',
            'parent_id.exists' => 'Wilayah induk tidak ditemukan.',
            'leader_name.max' => 'Nama ketua maksimal :max karakter.',
            'latitude.between' => 'Lintang harus antara -90 dan 90.',
            'longitude.between' => 'Bujur harus antara -180 dan 180.',
            'maps_url.url' => 'URL Google Maps harus berupa tautan http atau https.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function areaAttributes(): array
    {
        return [
            'name' => 'nama',
            'slug' => 'slug',
            'type' => 'tipe area',
            'parent_id' => 'wilayah induk',
            'description' => 'deskripsi',
            'leader_name' => 'nama ketua',
            'contact_phone' => 'nomor kontak',
            'address' => 'alamat',
            'latitude' => 'lintang',
            'longitude' => 'bujur',
            'maps_url' => 'tautan peta',
        ];
    }

    protected function prepareAreaInput(): void
    {
        $input = [];

        foreach (['name', 'slug', 'description', 'leader_name', 'contact_phone', 'address', 'maps_url'] as $field) {
            $value = $this->input($field);
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        foreach (['description', 'leader_name', 'contact_phone', 'address', 'maps_url'] as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        if (($input['slug'] ?? null) === null || $input['slug'] === '') {
            $input['slug'] = Str::slug((string) $this->input('name'));
        }

        $parentId = $this->input('parent_id');
        $input['parent_id'] = ($parentId === null || $parentId === '') ? null : (int) $parentId;

        /*
         * Coordinates arrive as text and are stored as decimal(10,7), so they
         * are trimmed here and left as null when the box is empty. An empty
         * string is not zero: 0,0 is a real point in the world and must not be
         * what "nobody filled this in" turns into.
         */
        foreach (['latitude', 'longitude'] as $field) {
            $value = $this->input($field);
            $trimmed = is_string($value) ? trim($value) : $value;
            $input[$field] = ($trimmed === null || $trimmed === '') ? null : $trimmed;
        }

        $input['show_contact'] = (bool) filter_var($this->input('show_contact'), FILTER_VALIDATE_BOOLEAN);
        $input['is_active'] = (bool) filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN);
        $input['sort_order'] = max(0, (int) $this->input('sort_order', 0));

        $this->merge($input);
    }

    /**
     * Reject a coordinate pair that is only half filled (D-61).
     *
     * `after()` is the only safe place to add an error for a cross-field rule:
     * Validator::passes() starts by resetting the message bag, so an error added
     * while the validator is still being built is wiped before the rules run —
     * a check that looks like it works and never fires. The same note stands in
     * LocationValidationRules, which checks the same thing for locations.
     *
     * @param  array<string, mixed>  $data
     */
    private function rejectHalfFilledCoordinates(Validator $validator, array $data): void
    {
        $hasLatitude = $this->hasCoordinate($data['latitude'] ?? null);
        $hasLongitude = $this->hasCoordinate($data['longitude'] ?? null);

        // One half of a pair cannot build a map link, so storing it would be
        // storing a number nothing can use.
        if ($hasLatitude xor $hasLongitude) {
            $validator->errors()->add(
                'longitude',
                'Koordinat lintang dan bujur harus diisi keduanya atau dikosongkan keduanya.'
            );
        }
    }

    /**
     * Whether a posted coordinate counts as present.
     *
     * An untouched number input posts an empty string, which is absent rather
     * than a value — treating it as present would reject every form that filled
     * only one of the two.
     */
    private function hasCoordinate(mixed $value): bool
    {
        return $value !== null && $value !== '' && trim((string) $value) !== '';
    }

    /**
     * The hierarchy rule of PRD COM-02 / D-55 point 3.
     *
     * @return Closure(Validator): void
     */
    protected function areaBusinessRules(?Area $existing = null): Closure
    {
        return function (Validator $validator) use ($existing): void {
            /** @var array<string, mixed> $data */
            $data = $validator->validated();

            $this->rejectHalfFilledCoordinates($validator, $data);

            $rawType = $data['type'] ?? null;
            $type = $rawType instanceof AreaType ? $rawType : AreaType::tryFrom((string) $rawType);

            if ($type === null) {
                return;
            }

            $parentId = $data['parent_id'] ?? null;

            if ($type === AreaType::Lingkungan) {
                // A lingkungan may have no parent: this parish has no wilayah, so
                // its centre lingkungan are top-level (D-57). When a parent is
                // given, it must be an active wilayah.
                if ($parentId === null) {
                    return;
                }

                $parent = Area::query()->find((int) $parentId);

                if ($parent === null) {
                    $validator->errors()->add('parent_id', 'Wilayah induk tidak ditemukan.');
                } elseif ($parent->type !== AreaType::Wilayah) {
                    $validator->errors()->add('parent_id', 'Wilayah induk harus bertipe wilayah, bukan lingkungan atau stasi.');
                } elseif ($existing !== null && $parent->getKey() === $existing->getKey()) {
                    $validator->errors()->add('parent_id', 'Sebuah lingkungan tidak boleh menjadi induk dirinya sendiri.');
                } elseif (! $parent->is_active && ($existing === null || $existing->parent_id !== $parent->getKey())) {
                    $validator->errors()->add('parent_id', 'Wilayah induk sedang nonaktif.');
                }

                return;
            }

            // wilayah and stasi are top-level.
            if ($parentId !== null) {
                $validator->errors()->add('parent_id', 'Wilayah dan stasi tidak boleh memiliki induk.');
            }

            // An area with children must stay a wilayah, or the two-level rule
            // breaks from the other direction.
            if ($existing !== null && $existing->children()->exists()) {
                $validator->errors()->add('type', 'Area ini masih memiliki lingkungan. Pindahkan atau hapus lingkungan tersebut terlebih dahulu.');
            }
        };
    }
}
