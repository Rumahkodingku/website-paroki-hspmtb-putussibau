<?php

namespace App\Concerns;

use App\Enums\MediaStatus;
use App\Enums\PostStatus;
use App\Models\Post;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The rules for an article's own fields, shared by the store and the update
 * request so the two cannot drift apart.
 *
 * Three of them are not expressible as a rule on a field, and each has a shape
 * of its own:
 *
 *   - content is sanitized on the way in, in prepareForValidation(). The
 *     editor lives in the browser, which is not a guarantee about the request
 *     body, and PRD D-14 is explicit that client-side checking is not the
 *     substitute. See D-25.
 *
 *   - published_at arrives as whatever a datetime-local input sends — a local
 *     wall clock with no offset — and is read in app.display_timezone and
 *     converted to UTC (D-01). A rule can reject but never rewrite, so the
 *     conversion has to happen before the rules run.
 *
 *   - POST-07 caps the pinned strip at two. That is a rule about the database
 *     rather than about a field, so it counts other rows.
 *
 * view_count is deliberately absent. It is written by RecordPostViewAction and
 * by nothing else; accepting it from the request body would let a visitor set
 * the number a card displays.
 *
 * @see docs/PRD.md 7.4, POST-01 through POST-10, D-11, D-14
 * @see docs/DECISIONS.md D-25, D-43
 */
trait PostValidationRules
{
    /**
     * @param  Post|null  $ignore  the record being updated, so it keeps its own slug and its own pin slot
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function postRules(?Post $ignore = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            // Nullable rather than required: POST-01 says the slug is generated
            // from the title when the field is empty. The generated value still
            // has to pass `unique` before the row is written, which is what
            // SavePostAction's suffix loop is for — a suffix is a value this
            // layer cannot know.
            'slug' => [
                'nullable',
                'string',
                'alpha_dash:ascii',
                'lowercase',
                'max:255',
                // No withoutTrashed(). A soft-deleted article keeps its slug on
                // purpose, because the public detail route binds by slug and
                // two rows answering one slug would make that binding
                // ambiguous. See D-45.
                Rule::unique('posts', 'slug')->ignore($ignore?->getKey()),
            ],
            'post_category_id' => [
                'required',
                'integer',
                Rule::exists('post_categories', 'id'),
            ],
            'author_name' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:1000'],
            // The same ceiling as config('html.max_input_length'), stated here
            // so the message an admin sees is about the field they filled in.
            'content' => ['required', 'string', 'max:500000'],
            'featured_image' => ['nullable', 'string', 'max:255'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'scripture_reference' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(PostStatus::class)],
            // after or equal, not after: an admin scheduling an article for this
            // exact minute should not be told to wait one minute more.
            'published_at' => ['nullable', 'date', 'after_or_equal:now'],
            'is_pinned' => ['required', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'media_id' => [
                'nullable',
                'integer',
                // Ready, not merely existing. An upload goes through the queue
                // (XC-M1), so a media row exists for a moment with no variants
                // on disk; storing its id then would publish an image that 404s.
                Rule::exists('media', 'id')->where('status', MediaStatus::Ready->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function postMessages(): array
    {
        return [
            'title.required' => 'Judul artikel wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'slug.lowercase' => 'Slug hanya boleh berisi huruf kecil.',
            'slug.unique' => 'Slug ini sudah dipakai artikel lain. Kosongkan untuk dibuat otomatis.',
            'post_category_id.required' => 'Kategori wajib dipilih.',
            'post_category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'author_name.max' => 'Nama penulis maksimal :max karakter.',
            'excerpt.max' => 'Ringkasan maksimal :max karakter.',
            'content.required' => 'Isi artikel wajib diisi.',
            'content.max' => 'Isi artikel terlalu panjang untuk disimpan.',
            'status.required' => 'Status artikel wajib dipilih.',
            'status.enum' => 'Status artikel tidak valid.',
            'published_at.after_or_equal' => 'Waktu terbit tidak boleh di masa lalu.',
            'is_pinned.required' => 'Pilih apakah artikel ini disematkan.',
            'meta_description.max' => 'Meta description maksimal :max karakter.',
            'media_id.exists' => 'Gambar yang dipilih belum selesai diproses. Tunggu sebentar lalu coba lagi.',
        ];
    }

    /**
     * Sanitize the body and read the local wall clock as UTC.
     *
     * Both happen here, before the rules run, because neither can be expressed
     * as a rule: a rule rejects a value, it cannot rewrite one.
     */
    protected function normalisePostInput(): void
    {
        $this->merge([
            'content' => app(HtmlSanitizer::class)->clean($this->input('content')),
            'published_at' => $this->toUtc($this->input('published_at')),
        ]);
    }

    /**
     * An empty string is a cleared optional field, not the epoch.
     */
    protected function toUtc(mixed $value): mixed
    {
        if (! is_string($value) || trim($value) === '') {
            return $value === '' ? null : $value;
        }

        return Carbon::parse($value, config('app.display_timezone'))->utc();
    }

    /**
     * POST-07: at most two published articles are pinned at a time.
     *
     * Counted over the rows rather than over the request, which is the whole
     * reason this cannot be a rule on `is_pinned`. $ignore is the article being
     * edited, so re-saving a pinned article without touching the box does not
     * find itself in the way.
     *
     * Roadmap 7.3 asks for this to be safe against two requests at once and
     * then says the requirement is that the handling be consistent and tested —
     * which is what this is. A check that reads then writes cannot be made
     * atomic without serialising every article save behind a lock, and the cost
     * is paid on every keystroke saved to buy a guarantee on a two-admin
     * collision. The public query caps its own strip at two regardless, so even
     * a database that drifted never shows three.
     */
    protected function rejectThirdPinnedPost(Validator $validator, ?Post $ignore = null): void
    {
        $validator->after(function (Validator $validator) use ($ignore): void {
            // boolean('is_pinned'), not boolean(input('is_pinned')). The
            // one-argument form takes a KEY; passing the value casts it to a
            // key that no field has, reads the default, and this check becomes
            // a check that never fails.
            if (! $this->boolean('is_pinned')) {
                return;
            }

            $alreadyPinned = Post::query()
                ->pinned()
                ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->getKey()))
                ->count();

            if ($alreadyPinned >= 2) {
                $validator->errors()->add(
                    'is_pinned',
                    __('Maksimal dua artikel aktif dapat disematkan. Lepaskan salah satu sebelum menyematkan yang ini.')
                );
            }
        });
    }

    /**
     * A scheduled article needs a date, and it needs it in the future.
     *
     * Attached as an `after` hook rather than a rule on published_at because
     * the requirement is conditional: an empty published_at is fine for a draft
     * and wrong for a scheduled one.
     */
    protected function requireFuturePublicationForScheduled(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('status') !== PostStatus::Scheduled->value) {
                return;
            }

            $publishedAt = $this->input('published_at');

            if ($publishedAt === null || $publishedAt === '') {
                $validator->errors()->add(
                    'published_at',
                    __('Artikel terjadwal wajib memiliki waktu terbit di masa depan.')
                );

                return;
            }

            if (Carbon::parse($publishedAt)->isPast()) {
                $validator->errors()->add(
                    'published_at',
                    __('Waktu terbit artikel terjadwal harus di masa depan.')
                );
            }
        });
    }

    /**
     * The validated attributes, with the empty optional fields turned into real
     * nulls so they do not become empty strings in the database.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function postAttributes(array $validated): array
    {
        foreach (['author_name', 'excerpt', 'image_alt', 'scripture_reference', 'meta_title', 'meta_description', 'media_id'] as $key) {
            if (($validated[$key] ?? null) === '') {
                $validated[$key] = null;
            }
        }

        // featured_image is written by SavePostAction from the media row rather
        // than accepted here, so an edited article without a new upload keeps
        // the image it already has. Dropped from the payload for that reason.
        unset($validated['featured_image']);

        return $validated;
    }
}
