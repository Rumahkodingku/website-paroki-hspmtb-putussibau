<?php

namespace App\Http\Requests\Backup;

use App\Models\Backup;
use App\Models\BackupRestore;
use Illuminate\Foundation\Http\FormRequest;

class RestoreBackupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $backup = $this->route('backup');

        if (! $backup instanceof Backup) {
            return false;
        }

        if (! ($this->user()?->can('restore', $backup) ?? false)) {
            return false;
        }

        $restore = $this->restore();

        return $restore !== null && $restore->backup_id === $backup->getKey();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'restore_id' => ['required', 'integer'],
            'confirmation' => ['required', 'string', 'in:PULIHKAN DATA'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'restore_id.required' => 'Sesi persiapan pemulihan tidak ditemukan.',
            'confirmation.required' => 'Ketik frasa konfirmasi pemulihan.',
            'confirmation.in' => 'Frasa konfirmasi pemulihan tidak sesuai.',
        ];
    }

    public function restore(): ?BackupRestore
    {
        $id = $this->input('restore_id');

        if (! is_numeric($id)) {
            return null;
        }

        return BackupRestore::query()->find((int) $id);
    }
}
