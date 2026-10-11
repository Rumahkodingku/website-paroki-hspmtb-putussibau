<?php

namespace App\Concerns;

use App\Enums\EventStatus;
use App\Enums\MediaStatus;
use App\Models\Event;
use App\Services\HtmlSanitizer;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * The rules for an activity's own fields, shared by the store and the update
 * request so the two cannot drift apart.
 *
 * The interesting decisions:
 *
 *   - start_at and end_at arrive as local wall clocks from datetime-local inputs
 *     and are read in app.display_timezone and converted to UTC (D-01, EVT-03).
 *     A rule can reject but never rewrite, so the conversion runs before.
 *
 *   - end_at is after_or_equal rather than after (EVT-01): an activity that
 *     begins and ends in the same minute is odd but not invalid, and rejecting
 *     it would force an admin to invent a duration.
 *
 *   - location_id does NOT require an active location, which is the opposite of
 *     ValidatesActiveLocationReference. That trait exists because a mass
 *     pointing at a retired location would never appear on the public page. An
 *     activity is different: a parish deactivates a location when it retires,
 *     and the activity that was announced there still happened. Refusing the
 *     reference would mean the parish has to rewrite its history to save an
 *     agenda entry. The free-text location_name is kept for exactly this reason.
 *
 *   - description is sanitized on the way in, like a post body (PRD D-14).
 *
 * @see docs/PRD.md 7.5, EVT-01, EVT-03, EVT-06, D-01, D-14
 * @see docs/DECISIONS.md D-44
 */
trait EventValidationRules
{
    /**
     * @param  Event|null  $ignore  the record being updated, so it keeps its own slug
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function eventRules(?Event $ignore = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'alpha_dash:ascii',
                'lowercase',
                'max:255',
                // No withoutTrashed(), for the reason PostValidationRules gives:
                // the public route binds by slug.
                Rule::unique('events', 'slug')->ignore($ignore?->getKey()),
            ],
            'event_category_id' => [
                'required',
                'integer',
                Rule::exists('event_categories', 'id'),
            ],
            'description' => ['nullable', 'string', 'max:50000'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'is_all_day' => ['required', 'boolean'],
            'location_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id'),
            ],
            'location_name' => ['nullable', 'string', 'max:255'],
            'poster_alt' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(EventStatus::class)],
            'is_published' => ['required', 'boolean'],
            'media_id' => [
                'nullable',
                'integer',
                // Ready, not merely existing — see PostValidationRules.
                Rule::exists('media', 'id')->where('status', MediaStatus::Ready->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function eventMessages(): array
    {
        return [
            'title.required' => 'Judul kegiatan wajib diisi.',
            'slug.alpha_dash' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung.',
            'slug.lowercase' => 'Slug hanya boleh berisi huruf kecil.',
            'slug.unique' => 'Slug ini sudah dipakai kegiatan lain. Kosongkan untuk dibuat otomatis.',
            'event_category_id.required' => 'Kategori wajib dipilih.',
            'event_category_id.exists' => 'Kategori yang dipilih tidak tersedia.',
            'description.max' => 'Deskripsi terlalu panjang untuk disimpan.',
            'start_at.required' => 'Tanggal kegiatan wajib diisi.',
            'end_at.after_or_equal' => 'Waktu selesai tidak boleh lebih awal dari waktu mulai.',
            'is_all_day.required' => 'Pilih apakah kegiatan berlangsung sepanjang hari.',
            'is_published.required' => 'Pilih apakah kegiatan ini tampil di situs.',
            'location_id.exists' => 'Lokasi yang dipilih tidak tersedia.',
            'status.required' => 'Status kegiatan wajib dipilih.',
            'status.enum' => 'Status kegiatan tidak valid.',
            'media_id.exists' => 'Poster yang dipilih belum selesai diproses. Tunggu sebentar lalu coba lagi.',
        ];
    }

    /**
     * Convert the local wall clocks to UTC and sanitize the description.
     *
     * Both happen here, before the rules run, because neither can be a rule.
     */
    protected function normaliseEventInput(): void
    {
        $this->merge([
            'description' => app(HtmlSanitizer::class)->clean($this->input('description')),
            'start_at' => $this->toUtc($this->input('start_at')),
            'end_at' => $this->toUtc($this->input('end_at')),
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
     * An all-day activity needs a date, not a clock.
     *
     * Roadmap 8.1 says the admin is not forced to invent a time of day. The
     * start_at rule above is still `required` for an all-day event — the date
     * itself is the thing being stored, and a date without a date is not a
     * whole day. The check that would matter is the reverse one: an all-day
     * event whose end lands on a different date is not whole-day, and the .ics
     * writer depends on knowing which it is.
     */
    protected function rejectSpanningAllDayEvent(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            // boolean('is_all_day'), not boolean(input('is_all_day')) — the
            // one-argument form takes a KEY, and passing the value reads a
            // field that does not exist, so the check would never fail.
            if (! $this->boolean('is_all_day')) {
                return;
            }

            $start = $this->input('start_at');
            $end = $this->input('end_at');

            if ($start === null || $end === null || $end === '') {
                return;
            }

            if (Carbon::parse($start)->toDateString() !== Carbon::parse($end)->toDateString()) {
                $validator->errors()->add(
                    'end_at',
                    __('Kegiatan sepanjang hari tidak boleh berlangsung lebih dari satu hari. Matikan "Sepanjang hari" bila kegiatan berlanjut ke hari berikutnya.')
                );
            }
        });
    }

    /**
     * Empty optional fields become real nulls rather than empty strings.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function eventAttributes(array $validated): array
    {
        foreach (['description', 'location_id', 'location_name', 'poster_alt', 'media_id'] as $key) {
            if (($validated[$key] ?? null) === '') {
                $validated[$key] = null;
            }
        }

        // poster_path is written by SaveEventAction from the media row rather
        // than accepted here, so an edit without a new upload keeps the poster
        // the activity already had.
        unset($validated['poster_path']);

        return $validated;
    }
}
