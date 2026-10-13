<?php

namespace App\Http\Requests\Profil;

use App\Concerns\ProfilValidationRules;
use App\Models\ParishBoardMember;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * @see docs/DECISIONS.md D-52
 */
class UpdateParishBoardMemberRequest extends FormRequest
{
    use ProfilValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->member()) ?? false;
    }

    /**
     * @return array<string, array<int, ValidationRule|array<mixed>|string>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'position' => ['required', 'string', 'max:100'],
            'photo_media_id' => ['nullable', 'integer', $this->readyMediaRule()],
            'photo_alt' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
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
            'name.required' => 'Nama pengurus wajib diisi.',
            'name.max' => 'Nama pengurus maksimal :max karakter.',
            'position.required' => 'Jabatan wajib diisi.',
            'position.max' => 'Jabatan maksimal :max karakter.',
            'photo_media_id.exists' => $this->mediaReadyMessage(),
            'description.max' => 'Keterangan terlalu panjang.',
            'sort_order.integer' => 'Urutan harus berupa angka.',
            'is_published.required' => 'Tentukan apakah anggota ini diterbitkan.',
        ];
    }

    private function member(): ParishBoardMember
    {
        /** @var ParishBoardMember $member */
        $member = $this->route('member');

        return $member;
    }
}
