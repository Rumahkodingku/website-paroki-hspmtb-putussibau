<?php

namespace App\Http\Requests\Profil;

use App\Concerns\ProfilValidationRules;
use App\Models\Pastor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-52
 */
class UpdatePastorRequest extends FormRequest
{
    use ProfilValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->pastor()) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->sanitizeHtmlFields(['bio']);
        $sortOrder = $this->input('sort_order');
        $this->merge(['sort_order' => ($sortOrder === null || $sortOrder === '') ? 0 : (int) $sortOrder]);
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'title' => ['nullable', 'string', 'max:50'],
            'photo_media_id' => ['nullable', 'integer', $this->readyMediaRule()],
            'photo_alt' => ['nullable', 'string', 'max:255'],
            'bio' => ['nullable', 'string', 'max:500000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_published' => ['required', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pastor wajib diisi.',
            'name.max' => 'Nama pastor maksimal :max karakter.',
            'title.max' => 'Gelar maksimal :max karakter.',
            'photo_media_id.exists' => $this->mediaReadyMessage(),
            'bio.max' => 'Biografi terlalu panjang untuk disimpan.',
            'sort_order.integer' => 'Urutan harus berupa angka.',
            'is_published.required' => 'Tentukan apakah pastor ini diterbitkan.',
        ];
    }

    private function pastor(): Pastor
    {
        /** @var Pastor $pastor */
        $pastor = $this->route('pastor');

        return $pastor;
    }
}
