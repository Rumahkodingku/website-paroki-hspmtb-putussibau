<?php

namespace App\Concerns;

use App\Enums\CommunityStatus;
use App\Enums\MediaStatus;
use App\Models\Community;
use App\Models\CommunityCategory;
use App\Services\HtmlSanitizer;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The rules, messages and normalization for a community, shared by the store and
 * update request so they cannot drift apart.
 *
 * Three things here are easy to get wrong and are the reason it is a file rather
 * than a dozen lines in each request:
 *
 * 1. The logo and thumbnail must point at a media row whose variants are already
 *    on disk. An upload is queued, so a Pending row would publish an image that
 *    404s. Same rule the Berita and Pelayanan requests use.
 *
 * 2. A category is required and, for a new community, must be active. The
 *    category a community already sits in may stay even if it has since been
 *    deactivated, or a deactivated category would freeze every community inside
 *    it (roadmap 5.1).
 *
 * 3. Areas are validated twice: each id must exist, and an inactive area may only
 *    stay if the community already serves it. Roadmap 5.3 says an inactive area
 *    is not offered for a new relationship but the relationships already made are
 *    not lost, and that distinction cannot be a field rule.
 *
 * published_at is not in the rules at all: it is derived in SaveCommunityAction,
 * never posted (D-55 point 9).
 *
 * @see docs/DECISIONS.md D-55
 */
trait CommunityValidationRules
{
    /**
     * @param  Community|null  $ignore  the record being updated, so it keeps its own slug
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    protected function communityRules(?Community $ignore = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:180',
                // No withoutTrashed(): the public route binds {community:slug},
                // so a slug used once is never reused (D-45, D-55 point 7).
                Rule::unique('communities', 'slug')->ignore($ignore?->getKey()),
            ],
            'community_category_id' => ['required', 'integer', Rule::exists('community_categories', 'id')],
            'logo_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('status', MediaStatus::Ready->value)],
            'logo_alt' => ['nullable', 'string', 'max:255'],
            'thumbnail_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('status', MediaStatus::Ready->value)],
            'thumbnail_alt' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            // Sanitized HTML (D-60), so max is measured on the markup rather than
            // on what the admin sees. The same ceiling the sanitizer's own
            // max_input_length would reject, kept small enough that a vision
            // statement stays a vision statement.
            'vision' => ['nullable', 'string', 'max:5000'],
            'mission' => ['nullable', 'string', 'max:5000'],
            'meeting_schedule' => ['nullable', 'string', 'max:5000'],
            'meeting_location' => ['nullable', 'string', 'max:255'],
            'eligibility' => ['nullable', 'string', 'max:5000'],
            'joining_info' => ['nullable', 'string', 'max:5000'],
            'contact_name' => ['nullable', 'string', 'max:255'],
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

            /*
             * The community's own links (D-59). Plain `url`, not a per-platform
             * host lock: facebook.com is not the only Facebook address a parish
             * uses, and fb.me or youtu.be are the links people actually paste.
             * Same rule the parish profile's website field uses.
             */
            'facebook' => ['nullable', 'url', 'max:255'],
            'instagram' => ['nullable', 'url', 'max:255'],
            'tiktok' => ['nullable', 'url', 'max:255'],
            'youtube' => ['nullable', 'url', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'status' => ['required', Rule::enum(CommunityStatus::class)],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function communityMessages(): array
    {
        return [
            'name.required' => 'Nama komunitas wajib diisi.',
            'name.max' => 'Nama komunitas maksimal :max karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'slug.unique' => 'Slug sudah dipakai komunitas lain.',
            'community_category_id.required' => 'Kategori komunitas wajib dipilih.',
            'community_category_id.exists' => 'Kategori komunitas tidak ditemukan.',
            'logo_media_id.exists' => 'Logo yang dipilih belum selesai diproses. Tunggu sebentar lalu coba lagi.',
            'thumbnail_media_id.exists' => 'Thumbnail yang dipilih belum selesai diproses. Tunggu sebentar lalu coba lagi.',
            'logo_alt.max' => 'Teks alternatif logo maksimal :max karakter.',
            'thumbnail_alt.max' => 'Teks alternatif thumbnail maksimal :max karakter.',
            'description.max' => 'Deskripsi maksimal :max karakter.',
            'vision.max' => 'Visi maksimal :max karakter.',
            'mission.max' => 'Misi maksimal :max karakter.',
            'meeting_location.max' => 'Lokasi pertemuan maksimal :max karakter.',
            'facebook.url' => 'Tautan Facebook tidak valid.',
            'facebook.max' => 'Tautan Facebook maksimal :max karakter.',
            'instagram.url' => 'Tautan Instagram tidak valid.',
            'instagram.max' => 'Tautan Instagram maksimal :max karakter.',
            'tiktok.url' => 'Tautan TikTok tidak valid.',
            'tiktok.max' => 'Tautan TikTok maksimal :max karakter.',
            'youtube.url' => 'Tautan YouTube tidak valid.',
            'youtube.max' => 'Tautan YouTube maksimal :max karakter.',
            'website.url' => 'Alamat situs web komunitas tidak valid.',
            'website.max' => 'Alamat situs web komunitas maksimal :max karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function communityAttributes(): array
    {
        return [
            'name' => 'nama komunitas',
            'slug' => 'slug',
            'community_category_id' => 'kategori',
            'logo_media_id' => 'logo',
            'thumbnail_media_id' => 'thumbnail',
            'description' => 'deskripsi',
            'vision' => 'visi',
            'mission' => 'misi',
            'meeting_schedule' => 'jadwal pertemuan',
            'meeting_location' => 'lokasi pertemuan',
            'eligibility' => 'persyaratan bergabung',
            'joining_info' => 'cara bergabung',
            'contact_name' => 'nama kontak',
            'contact_phone' => 'nomor kontak',
            'website' => 'situs web komunitas',
            'status' => 'status',
        ];
    }

    /**
     * Coerce and clean the payload before the rules run.
     */
    protected function prepareCommunityInput(): void
    {
        $input = [];

        $textFields = [
            'name', 'slug', 'description', 'vision', 'mission', 'meeting_schedule',
            'meeting_location', 'eligibility', 'joining_info', 'contact_name',
            'contact_phone', 'logo_alt', 'thumbnail_alt',
            'facebook', 'instagram', 'tiktok', 'youtube', 'website',
        ];

        foreach ($textFields as $field) {
            $value = $this->input($field);
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        // An untouched optional textarea or input is absent, not an empty string.
        $nullableFields = [
            'description', 'vision', 'mission', 'meeting_schedule', 'meeting_location',
            'eligibility', 'joining_info', 'contact_name', 'contact_phone',
            'logo_alt', 'thumbnail_alt',
            'facebook', 'instagram', 'tiktok', 'youtube', 'website',
        ];

        foreach ($nullableFields as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        if (($input['slug'] ?? null) === null || $input['slug'] === '') {
            $input['slug'] = Str::slug((string) $this->input('name'));
        }

        // An empty media picker posts nothing or an empty string; both mean "no
        // image", not media id 0.
        foreach (['logo_media_id', 'thumbnail_media_id'] as $field) {
            $value = $this->input($field);
            $input[$field] = ($value === null || $value === '') ? null : (int) $value;
        }

        $input['show_contact'] = (bool) filter_var($this->input('show_contact'), FILTER_VALIDATE_BOOLEAN);
        $input['is_published'] = (bool) filter_var($this->input('is_published'), FILTER_VALIDATE_BOOLEAN);

        $this->merge($input);

        /*
         * Last, deliberately. vision and mission are rich text (D-60), and this
         * merge is the last thing to touch the payload - sanitizing before it
         * would write the cleaned values and then overwrite them again with the
         * trimmed originals two lines later, which is the kind of bug that reads
         * as "sanitizing does nothing" rather than as a lost merge.
         */
        $this->sanitizeHtmlFields(['vision', 'mission']);
    }

    /**
     * Clean the given fields as sanitized HTML, turning a cleared one into null.
     *
     * The same method exists in ServiceValidationRules and ProfilValidationRules;
     * three copies is a smell, but the alternative is a shared trait for a
     * fourteen-line helper, and D-61's cleanup should decide that once rather
     * than three modules guessing differently today.
     *
     * @param  list<string>  $fields
     */
    protected function sanitizeHtmlFields(array $fields): void
    {
        $sanitizer = app(HtmlSanitizer::class);
        $merge = [];

        foreach ($fields as $field) {
            $clean = $sanitizer->clean(
                is_string($this->input($field)) ? $this->input($field) : null,
            );

            $merge[$field] = $this->isBlankMarkup($clean) ? null : $clean;
        }

        $this->merge($merge);
    }

    /**
     * Is this cleaned markup an editor that was emptied rather than written?
     *
     * Tiptap does not post an empty string for a blank document, it posts
     * `<p></p>`. Stored as-is that becomes a truthy value, so the public page
     * draws a "Visi" heading over nothing - the one case where switching a
     * textarea for a rich text editor shows up as a visible bug.
     *
     * Deliberately narrow: it matches only the empty-paragraph wrappers an
     * emptied document is made of. A document holding an image is `<p><img
     * ...></p>`, which does not match, so a vision that is one picture survives.
     * A text-extraction check would have dropped it, which is why this is a
     * pattern and not HtmlSanitizer::toPlainText().
     */
    private function isBlankMarkup(?string $clean): bool
    {
        return $clean === null
            || $clean === ''
            || preg_match('/^(?:<p>\s*(?:<br\s*\/?>)?\s*<\/p>|<br\s*\/?>|\s)+$/i', $clean) === 1;
    }

    /**
     * The cross-field rules that only make sense once the whole payload is in.
     *
     * @return Closure(Validator): void
     */
    protected function communityBusinessRules(?Community $existing = null): Closure
    {
        return function (Validator $validator) use ($existing): void {
            /** @var array<string, mixed> $data */
            $data = $validator->validated();

            $this->assertCategoryActive($validator, $data, $existing);
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertCategoryActive(Validator $validator, array $data, ?Community $existing): void
    {
        $categoryId = (int) ($data['community_category_id'] ?? 0);
        $category = CommunityCategory::query()->whereKey($categoryId)->first();
        $unchanged = $existing !== null && $categoryId === $existing->community_category_id;

        // An inactive category cannot be chosen for a new community, and cannot
        // be moved to on an update. The category a community already sits in may
        // stay (roadmap 5.1).
        if ($category !== null && ! $category->is_active && ! $unchanged) {
            $validator->errors()->add(
                'community_category_id',
                'Kategori ini sedang nonaktif. Pilih kategori aktif lain atau aktifkan kategorinya lebih dahulu.'
            );
        }
    }
}
