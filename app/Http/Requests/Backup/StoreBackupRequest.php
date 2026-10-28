<?php

namespace App\Http\Requests\Backup;

use App\Enums\BackupType;
use App\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Backup::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(BackupType::values())],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Pilih jenis backup.',
            'type.in' => 'Jenis backup tidak dikenal.',
            'note.max' => 'Catatan maksimal :max karakter.',
        ];
    }

    public function backupType(): BackupType
    {
        return BackupType::from((string) $this->validated('type'));
    }

    public function note(): ?string
    {
        $note = $this->validated('note');

        return is_string($note) && trim($note) !== '' ? trim($note) : null;
    }
}
