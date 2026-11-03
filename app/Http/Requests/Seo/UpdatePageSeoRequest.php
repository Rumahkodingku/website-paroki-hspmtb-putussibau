<?php

namespace App\Http\Requests\Seo;

use App\Enums\MediaStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates the static-page SEO form.
 *
 * The browser sends page keys, but it does not get to invent them: the array
 * is checked against config/seo.php before any value reaches the database.
 */
class UpdatePageSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('seo.update');
    }

    protected function prepareForValidation(): void
    {
        $pages = $this->input('pages');

        if (! is_array($pages)) {
            return;
        }

        $normalised = [];

        foreach ($pages as $key => $values) {
            if (! is_string($key) || ! is_array($values)) {
                continue;
            }

            $normalised[$key] = [
                'meta_title' => $this->nullIfBlank($values['meta_title'] ?? null),
                'meta_description' => $this->nullIfBlank($values['meta_description'] ?? null),
                'og_media_id' => $this->nullIfBlank($values['og_media_id'] ?? null),
            ];
        }

        $this->merge(['pages' => $normalised]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keys = $this->knownKeys();

        return [
            'pages' => [
                'required',
                'array',
                function (string $attribute, mixed $value, \Closure $fail) use ($keys): void {
                    $unknown = array_diff(array_keys((array) $value), $keys);

                    if ($unknown !== []) {
                        $fail('Halaman SEO tidak dikenal: '.implode(', ', $unknown));
                    }
                },
            ],
            'pages.*' => ['array'],
            'pages.*.meta_title' => ['nullable', 'string', 'max:255'],
            'pages.*.meta_description' => ['nullable', 'string', 'max:500'],
            'pages.*.og_media_id' => [
                'nullable',
                'string',
                Rule::exists('media', 'id')->where('status', MediaStatus::Ready->value),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pages.required' => 'Data SEO halaman wajib dikirim.',
            'pages.array' => 'Format SEO halaman tidak valid.',
            'pages.*.meta_title.max' => 'Judul SEO maksimal 255 karakter.',
            'pages.*.meta_description.max' => 'Deskripsi SEO maksimal 500 karakter.',
            'pages.*.og_media_id.exists' => 'Gambar yang dipilih belum selesai diproses. Tunggu sebentar lalu coba lagi.',
        ];
    }

    /**
     * The rows to write, limited to the configured pages.
     *
     * @return array<string, array{meta_title: string|null, meta_description: string|null, og_media_id: string|null}>
     */
    public function pagesPayload(): array
    {
        /** @var array<string, array<string, mixed>> $validated */
        $validated = $this->validated();
        $pages = $validated['pages'] ?? [];

        $result = [];

        foreach ($this->knownKeys() as $key) {
            $values = $pages[$key] ?? null;

            if (! is_array($values)) {
                continue;
            }

            $result[$key] = [
                'meta_title' => $this->nullableString($values['meta_title'] ?? null),
                'meta_description' => $this->nullableString($values['meta_description'] ?? null),
                'og_media_id' => $this->nullableString($values['og_media_id'] ?? null),
            ];
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    private function knownKeys(): array
    {
        /** @var array<string, array{route: string, label: string, title: string|null, description: string|null}> $pages */
        $pages = (array) config('seo.static_pages', []);

        return array_keys($pages);
    }

    private function nullIfBlank(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $value;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? trim($value) : null;
    }
}
