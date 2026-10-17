<?php

namespace App\Concerns;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentCategory;
use App\Rules\AllowedDocumentFile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

trait DocumentValidationRules
{
    /**
     * @return array<string, array<int, ValidationRule|Closure|string>>
     */
    protected function documentRules(?Document $ignore = null): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'document_category_id' => ['required', 'integer', Rule::exists('document_categories', 'id')],
            'description' => ['nullable', 'string', 'max:5000'],
            'file' => [
                $ignore === null ? 'required' : 'nullable',
                'file',
                'max:'.(int) config('documents.max_upload_kilobytes'),
                new AllowedDocumentFile,
            ],
            'version_label' => ['nullable', 'string', 'max:100'],
            'document_date' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(DocumentStatus::class)],
            'is_featured' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function documentMessages(): array
    {
        return [
            'title.required' => 'Judul dokumen wajib diisi.',
            'title.max' => 'Judul dokumen maksimal :max karakter.',
            'document_category_id.required' => 'Kategori dokumen wajib dipilih.',
            'document_category_id.exists' => 'Kategori dokumen tidak tersedia.',
            'description.max' => 'Deskripsi maksimal :max karakter.',
            'file.required' => 'Berkas dokumen wajib dipilih.',
            'file.file' => 'Berkas dokumen tidak valid.',
            'file.max' => 'Ukuran berkas maksimal 10 MB.',
            'version_label.max' => 'Label versi maksimal :max karakter.',
            'document_date.date' => 'Tanggal dokumen tidak valid.',
            'expires_at.date' => 'Tanggal berlaku tidak valid.',
            'status.required' => 'Status dokumen wajib dipilih.',
            'status.enum' => 'Status dokumen tidak valid.',
            'is_featured.required' => 'Pilih apakah dokumen ini unggulan.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function documentAttributes(): array
    {
        return [
            'title' => 'judul dokumen',
            'document_category_id' => 'kategori',
            'description' => 'deskripsi',
            'file' => 'berkas',
            'version_label' => 'versi',
            'document_date' => 'tanggal dokumen',
            'expires_at' => 'berlaku sampai',
            'status' => 'status',
            'is_featured' => 'unggulan',
        ];
    }

    protected function prepareDocumentInput(): void
    {
        $input = [];

        foreach (['title', 'description', 'version_label'] as $field) {
            $value = $this->input($field);
            $input[$field] = is_string($value) ? trim($value) : $value;
        }

        foreach (['description', 'version_label', 'document_date', 'expires_at'] as $field) {
            if (($input[$field] ?? null) === '') {
                $input[$field] = null;
            }
        }

        $input['is_featured'] = (bool) filter_var($this->input('is_featured'), FILTER_VALIDATE_BOOLEAN);

        $this->merge($input);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function documentPayloadAttributes(array $validated): array
    {
        unset($validated['file']);

        foreach (['description', 'version_label', 'document_date', 'expires_at'] as $key) {
            if (($validated[$key] ?? null) === '') {
                $validated[$key] = null;
            }
        }

        return $validated;
    }

    /**
     * @return Closure(Validator): void
     */
    protected function documentBusinessRules(?Document $existing = null): Closure
    {
        return function (Validator $validator) use ($existing): void {
            /** @var array<string, mixed> $data */
            $data = $validator->validated();

            $categoryId = (int) ($data['document_category_id'] ?? 0);
            $category = DocumentCategory::query()->whereKey($categoryId)->first();
            $unchanged = $existing !== null && $categoryId === $existing->document_category_id;

            if ($category !== null && ! $category->is_active && ! $unchanged) {
                $validator->errors()->add(
                    'document_category_id',
                    'Kategori yang dipilih sedang nonaktif. Pilih kategori lain.'
                );
            }

            $documentDate = $data['document_date'] ?? null;
            $expiresAt = $data['expires_at'] ?? null;

            if (is_string($documentDate) && is_string($expiresAt) && $expiresAt !== '' && $documentDate !== '') {
                if ($expiresAt < $documentDate) {
                    $validator->errors()->add(
                        'expires_at',
                        'Tanggal berlaku tidak boleh sebelum tanggal dokumen.'
                    );
                }
            }
        };
    }
}
