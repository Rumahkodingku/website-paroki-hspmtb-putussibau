<?php

namespace App\Concerns;

use App\Enums\LivestreamCategory;
use App\Enums\LivestreamEditorialStatus;
use App\Rules\YoutubeUrl;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

/**
 * The rules, messages and normalization for a livestream, shared by the store
 * and update request so they cannot drift apart.
 *
 * Three things are deliberate:
 *
 * 1. `youtube_video_id` is not a rule and not accepted from the browser. The
 *    save action derives it from `youtube_url` with App\Support\YoutubeEmbed,
 *    so a crafted form field cannot point the embed somewhere else.
 *
 * 2. `published_at` and `broadcast_status` are not rules either. The first is
 *    derived by the action; the second is changed only by the start/end/
 *    unavailable endpoints, never by a free PATCH.
 *
 * 3. Empty optional fields are normalized to null, so an untouched input does
 *    not store an empty string that later reads as a value.
 *
 * @see docs/DECISIONS.md D-101
 */
trait LivestreamValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|string>>
     */
    protected function livestreamRules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:150'],
            'youtube_url' => ['required', 'string', 'max:500', new YoutubeUrl],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['nullable', Rule::in(LivestreamCategory::values())],
            'location_id' => [
                'nullable',
                'integer',
                Rule::exists('locations', 'id')->where('is_active', true),
            ],
            'scheduled_at' => ['nullable', 'date'],
            'recorded_at' => ['nullable', 'date'],
            'is_recording_available' => ['required', 'boolean'],
            'status' => ['required', Rule::in(LivestreamEditorialStatus::values())],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function livestreamMessages(): array
    {
        return [
            'title.required' => 'Judul livestreaming wajib diisi.',
            'title.min' => 'Judul livestreaming minimal :min karakter.',
            'title.max' => 'Judul livestreaming maksimal :max karakter.',
            'youtube_url.required' => 'URL YouTube wajib diisi.',
            'youtube_url.max' => 'URL YouTube maksimal :max karakter.',
            'description.max' => 'Deskripsi maksimal :max karakter.',
            'category.in' => 'Kategori yang dipilih tidak valid.',
            'location_id.exists' => 'Lokasi yang dipilih tidak tersedia atau sedang nonaktif.',
            'scheduled_at.date' => 'Jadwal siaran tidak valid.',
            'recorded_at.date' => 'Waktu rekaman tidak valid.',
            'status.required' => 'Status publikasi wajib dipilih.',
            'status.in' => 'Status publikasi tidak valid.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function livestreamAttributes(): array
    {
        return [
            'title' => 'judul livestreaming',
            'youtube_url' => 'URL YouTube',
            'description' => 'deskripsi',
            'category' => 'kategori',
            'location_id' => 'lokasi',
            'scheduled_at' => 'jadwal siaran',
            'recorded_at' => 'waktu rekaman',
            'is_recording_available' => 'rekaman tersedia',
            'status' => 'status publikasi',
        ];
    }

    /**
     * Coerce and clean the payload before the rules run.
     */
    protected function prepareLivestreamInput(): void
    {
        $input = [];

        foreach (['title', 'youtube_url', 'description'] as $field) {
            $value = $this->input($field);
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        foreach (['description', 'category', 'location_id', 'scheduled_at', 'recorded_at'] as $field) {
            $value = $this->input($field);
            $input[$field] = $value === '' || $value === null
                ? null
                : (is_string($value) ? trim($value) : $value);
        }

        $input['is_recording_available'] = (bool) filter_var(
            $this->input('is_recording_available'),
            FILTER_VALIDATE_BOOLEAN,
        );

        $this->merge($input);
    }
}
