<?php

namespace App\Http\Requests\Backup;

use App\Models\Backup;
use Illuminate\Foundation\Http\FormRequest;

class PrepareRestoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $backup = $this->route('backup');

        return $backup instanceof Backup
            && ($this->user()?->can('restore', $backup) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
