<?php

namespace App\Http\Requests\Profil;

use App\Concerns\ProfilValidationRules;
use App\Models\ParishHistoryItem;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-52
 */
class StoreParishHistoryItemRequest extends FormRequest
{
    use ProfilValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('create', ParishHistoryItem::class) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeHtmlFields(['description']);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return $this->historyRules();
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->historyMessages();
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    protected function historyRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            // Both nullable: a parish often knows only the year, and must not be
            // forced to invent a date (roadmap 6.2).
            'year' => ['nullable', 'integer', 'min:1000', 'max:'.(date('Y') + 1)],
            'date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500000'],
            'media_id' => ['nullable', 'integer', $this->readyMediaRule()],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function historyMessages(): array
    {
        return [
            'title.required' => 'Judul peristiwa wajib diisi.',
            'title.max' => 'Judul peristiwa maksimal :max karakter.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.min' => 'Tahun tidak masuk akal.',
            'year.max' => 'Tahun tidak boleh jauh di masa depan.',
            'date.date' => 'Tanggal tidak valid.',
            'description.max' => 'Uraian terlalu panjang untuk disimpan.',
            'media_id.exists' => $this->mediaReadyMessage(),
            'sort_order.integer' => 'Urutan harus berupa angka.',
            'is_published.required' => 'Tentukan apakah peristiwa ini diterbitkan.',
        ];
    }
}
