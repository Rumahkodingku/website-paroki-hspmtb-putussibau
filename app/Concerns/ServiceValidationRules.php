<?php

namespace App\Concerns;

use App\Enums\MediaStatus;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Rules\GoogleFormUrl;
use App\Services\HtmlSanitizer;
use App\Support\PhoneNumber;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The rules, messages and normalization for a service, shared by the store and
 * the update request so they cannot drift apart.
 *
 * Three things here are easy to get wrong and are the reason it is a file rather
 * than a dozen lines in each request:
 *
 * 1. form_url is optional. SVC-05 allows a service with no Google Form, so the
 *    rule is nullable and the trailing slash of rules around it (is_form_open,
 *    emergency) normalize the contradictory states away rather than rejecting
 *    them. A form_url that is filled is still validated hard.
 *
 * 2. An emergency service cannot be activated without a contact number (SVC-06),
 *    and a service cannot be activated in a deactivated category (roadmap 6B-103).
 *    Both are cross-field checks, so they run in after() where the whole payload
 *    is visible.
 *
 * 3. introduction is rich text, sanitized on the way in before validation — the
 *    editor being in the browser says nothing about the request body (D-14).
 *
 * @see docs/DECISIONS.md D-54
 */
trait ServiceValidationRules
{
    /**
     * The closure on contact_phone is a rule too: Laravel accepts a closure as a
     * validation rule, and the array types below list it so PHPStan reads the
     * return type as it is rather than a subset of it.
     *
     * @param  Service|null  $ignore  the record being updated, so it keeps its own slug
     * @return array<string, array<int, Closure|ValidationRule|string>>
     */
    protected function serviceRules(?Service $ignore = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'alpha_dash',
                'max:255',
                // No withoutTrashed(): the public route binds {service:slug}, so
                // a slug used once is never reused (D-45, D-54).
                Rule::unique('services', 'slug')->ignore($ignore?->getKey()),
            ],
            'service_category_id' => ['required', 'integer', Rule::exists('service_categories', 'id')],
            // The thumbnail must point at a media row whose variants are already
            // on disk; an upload is queued, so a Pending row would publish an
            // image that 404s. Same rule the Berita and Profil requests use.
            'thumbnail_media_id' => ['nullable', 'integer', Rule::exists('media', 'id')->where('status', MediaStatus::Ready->value)],
            'thumbnail_alt' => ['nullable', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:2000'],
            'introduction' => ['nullable', 'string', 'max:500000'],
            'schedule_info' => ['nullable', 'string', 'max:5000'],
            'fee_info' => ['nullable', 'string', 'max:5000'],
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
            'form_url' => ['nullable', 'string', 'max:2048', new GoogleFormUrl],
            'form_button_label' => ['required', 'string', 'max:100'],
            'is_form_open' => ['required', 'boolean'],
            'form_closed_message' => ['nullable', 'string', 'max:2000'],
            'is_emergency' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function serviceMessages(): array
    {
        return [
            'title.required' => 'Nama layanan wajib diisi.',
            'title.max' => 'Nama layanan maksimal :max karakter.',
            'slug.required' => 'Slug wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'slug.unique' => 'Slug sudah dipakai layanan lain.',
            'service_category_id.required' => 'Kategori layanan wajib dipilih.',
            'service_category_id.exists' => 'Kategori layanan tidak ditemukan.',
            'thumbnail_media_id.exists' => 'Gambar yang dipilih belum selesai diproses. Tunggu sebentar lalu coba lagi.',
            'thumbnail_alt.max' => 'Teks alternatif gambar maksimal :max karakter.',
            'summary.max' => 'Ringkasan maksimal :max karakter.',
            'form_url.max' => 'Tautan Google Form terlalu panjang.',
            'form_button_label.required' => 'Label tombol pendaftaran wajib diisi.',
            'form_closed_message.max' => 'Pesan penutupan maksimal :max karakter.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function serviceAttributes(): array
    {
        return [
            'title' => 'nama layanan',
            'slug' => 'slug',
            'service_category_id' => 'kategori',
            'thumbnail_media_id' => 'thumbnail',
            'thumbnail_alt' => 'teks alternatif thumbnail',
            'summary' => 'ringkasan',
            'introduction' => 'pengantar',
            'schedule_info' => 'jadwal',
            'fee_info' => 'biaya',
            'contact_name' => 'nama penanggung jawab',
            'contact_phone' => 'nomor telepon',
            'form_url' => 'tautan Google Form',
            'form_button_label' => 'label tombol',
            'form_closed_message' => 'pesan penutupan',
        ];
    }

    /**
     * Coerce and clean the payload before the rules run.
     *
     * A rule can reject but never rewrite, so anything the form legitimately
     * sends in a form the rules do not expect — a blank slug, a form_url with an
     * emergency flag, an empty optional text field — is turned into the value it
     * actually means here.
     */
    protected function prepareServiceInput(): void
    {
        $input = [];

        foreach (['title', 'slug', 'summary', 'schedule_info', 'fee_info', 'contact_name', 'contact_phone', 'form_url', 'form_closed_message', 'thumbnail_alt'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $value = trim($value);
            }

            $input[$field] = $value;
        }

        // An untouched optional text input is absent, not an empty string.
        foreach (['summary', 'schedule_info', 'fee_info', 'contact_name', 'contact_phone', 'form_url', 'form_closed_message', 'thumbnail_alt'] as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        // An empty media picker posts nothing or an empty string; both mean "no
        // thumbnail", not media id 0.
        $thumbnailId = $this->input('thumbnail_media_id');
        $input['thumbnail_media_id'] = ($thumbnailId === null || $thumbnailId === '') ? null : (int) $thumbnailId;

        if (($input['slug'] ?? null) === null || $input['slug'] === '') {
            $input['slug'] = Str::slug((string) $this->input('title'));
        }

        $emergency = (bool) filter_var($this->input('is_emergency'), FILTER_VALIDATE_BOOLEAN);
        $formOpen = (bool) filter_var($this->input('is_form_open'), FILTER_VALIDATE_BOOLEAN);

        // SVC-05/6B: an emergency service never uses a form, and a form cannot be
        // open with no URL. Both become the state they mean rather than an error.
        if ($emergency) {
            $input['form_url'] = null;
            $formOpen = false;
        } elseif ($input['form_url'] === null) {
            $formOpen = false;
        }

        $input['is_emergency'] = $emergency;
        $input['is_form_open'] = $formOpen;
        $input['is_active'] = (bool) filter_var($this->input('is_active'), FILTER_VALIDATE_BOOLEAN);

        $label = trim((string) $this->input('form_button_label', ''));
        $input['form_button_label'] = $label === '' ? 'Daftar Sekarang' : $label;

        $input['sort_order'] = max(0, (int) $this->input('sort_order', 0));

        $this->merge($input);
    }

    /**
     * The cross-field rules that only make sense once the whole payload is in.
     *
     * @return Closure(Validator): void
     */
    protected function serviceBusinessRules(?Service $existing = null): Closure
    {
        return function (Validator $validator) use ($existing): void {
            /** @var array<string, mixed> $data */
            $data = $validator->validated();

            $isActive = (bool) ($data['is_active'] ?? false);
            $isEmergency = (bool) ($data['is_emergency'] ?? false);
            $categoryId = (int) ($data['service_category_id'] ?? 0);

            $category = ServiceCategory::query()->whereKey($categoryId)->first();
            $categoryActive = $category?->is_active === true;
            $unchangedCategory = $existing !== null && $categoryId === $existing->service_category_id;

            // 6B-103/6B-268: an inactive category cannot be chosen for a new
            // service, and cannot be moved to on an update. The category a service
            // already sits in may stay while the service is inactive — otherwise a
            // deactivated category would freeze every service inside it.
            if (! $categoryActive && (! $unchangedCategory || $isActive)) {
                $validator->errors()->add(
                    'service_category_id',
                    'Kategori ini sedang nonaktif. Pilih kategori aktif lain atau aktifkan kategorinya lebih dahulu.'
                );
            }

            // SVC-06/AC-SVC-9: a direct-contact emergency service needs a number
            // before it can be published.
            $phone = trim((string) ($data['contact_phone'] ?? ''));

            if ($isEmergency && $isActive && $phone === '') {
                $validator->errors()->add(
                    'contact_phone',
                    'Layanan darurat wajib memiliki nomor telepon sebelum diaktifkan.'
                );
            }
        };
    }

    /**
     * Sanitize each named HTML field in place (PRD D-14, XC-S1).
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

            $merge[$field] = $clean === '' ? null : $clean;
        }

        $this->merge($merge);
    }
}
